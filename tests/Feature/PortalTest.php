<?php

namespace Tests\Feature;

use App\Mail\PortalNotice;
use App\Models\Account;
use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Post;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $team;

    private User $otherTeam;

    private Account $noor;

    private Account $atlas;

    private User $noorUser;

    private User $atlasUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();

        $this->agency = Agency::create(['name' => 'Muannas Atelier', 'brand_color' => '#0f1e3d', 'max_workspaces' => 50, 'max_members' => 20]);
        Role::ensureDefaults($this->agency);
        $editor = Role::where('agency_id', $this->agency->id)->where('name', 'Editor')->first();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@t.test', 'password' => 'secret123', 'role' => 'owner', 'agency_id' => $this->agency->id]);
        $this->team = User::create(['name' => 'Sara', 'email' => 'team@t.test', 'password' => 'secret123', 'role' => 'member', 'agency_id' => $this->agency->id, 'role_id' => $editor->id]);
        $this->otherTeam = User::create(['name' => 'Omar', 'email' => 'omar@t.test', 'password' => 'secret123', 'role' => 'member', 'agency_id' => $this->agency->id, 'role_id' => $editor->id]);

        [$this->noor, $this->noorUser] = $this->client('Noor Boutique', 'noor@t.test');
        [$this->atlas, $this->atlasUser] = $this->client('Atlas Fitness', 'atlas@t.test');
        $this->noor->team()->attach($this->team->id);
        $this->atlas->team()->attach($this->otherTeam->id);
    }

    private function client(string $name, string $email): array
    {
        $a = Account::create(['agency_id' => $this->agency->id, 'name' => $name, 'token' => Str::random(24), 'share_enabled' => false]);
        $u = User::create(['name' => $name.' Owner', 'email' => $email, 'password' => 'secret123', 'role' => 'guest']);
        $a->people()->attach($u->id, ['perm' => 'comment']);

        return [$a, $u];
    }

    private function mkPost(Account $a, string $status = 'pending', array $extra = []): Post
    {
        $path = 'posts/'.$a->id.'/x.jpg';
        Storage::disk('local')->put($path, 'jpg');

        return $a->posts()->create($extra + ['title' => 'Post '.Str::random(4), 'platform' => 'instagram', 'status' => $status, 'media' => [$path], 'scheduled_on' => now()->addDay()->toDateString(), 'round' => 1]);
    }

    // ── access & roles ───────────────────────────────────────

    public function test_client_login_lands_on_own_dashboard_and_cannot_use_staff_pages(): void
    {
        $this->post('/login', ['email' => 'noor@t.test', 'password' => 'secret123'])->assertRedirect('/portal');
        $this->actingAs($this->noorUser)->get('/portal')->assertRedirect('/portal/c/'.$this->noor->id);
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertOk()->assertSee('Noor')->assertSee('You are all caught up')->assertSee('Needs your approval');
        foreach (['/portal/clients', '/portal/admin', '/team', '/board'] as $url) {
            $this->actingAs($this->noorUser)->get($url)->assertForbidden();
        }
    }

    public function test_clients_are_strictly_separated(): void
    {
        $mine = $this->mkPost($this->noor);
        $theirs = $this->mkPost($this->atlas);
        $inv = $this->atlas->invoices()->create(['number' => 'A-1', 'amount' => 10, 'currency' => 'AED', 'issued_on' => today(), 'due_on' => today()->addDays(10)]);
        $this->actingAs($this->noorUser);

        $this->get('/portal/c/'.$this->atlas->id)->assertForbidden();
        $this->get('/portal/c/'.$this->atlas->id.'/content')->assertForbidden();
        $this->get('/portal/c/'.$this->atlas->id.'/invoices')->assertForbidden();
        $this->get('/portal/posts/'.$theirs->id)->assertForbidden();
        $this->get('/portal/posts/'.$theirs->id.'/media/0')->assertForbidden();
        $this->get('/portal/invoices/'.$inv->id.'/pdf')->assertForbidden();
        $this->post('/portal/posts/'.$theirs->id.'/approve')->assertForbidden();
        $this->post('/portal/posts/'.$theirs->id.'/comments', ['body' => 'hi'])->assertForbidden();
        $this->get('/portal/posts/'.$mine->id)->assertOk();
        $this->get('/api/accounts/'.$this->noor->id)->assertForbidden(); // internal planning board API stays closed to clients
    }

    public function test_team_member_only_sees_assigned_clients_but_admin_sees_all(): void
    {
        $this->actingAs($this->team)->get('/portal/c/'.$this->noor->id)->assertOk();
        $this->actingAs($this->team)->get('/portal/c/'.$this->atlas->id)->assertForbidden();
        $this->actingAs($this->team)->get('/portal/clients')->assertOk()->assertSee('Noor Boutique')->assertDontSee('Atlas Fitness');
        $this->actingAs($this->admin)->get('/portal/clients')->assertOk()->assertSee('Noor Boutique')->assertSee('Atlas Fitness');
        $this->actingAs($this->admin)->get('/portal')->assertRedirect('/portal/clients');
        $this->actingAs($this->admin)->get('/portal/clients?q=Atlas')->assertOk()->assertSee('/portal/clients/'.$this->atlas->id.'"', false)->assertDontSee('/portal/clients/'.$this->noor->id.'"', false);
    }

    public function test_suspended_agency_locks_everyone_out(): void
    {
        $this->agency->update(['status' => 'suspended']);
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertForbidden();
    }

    // ── content review ───────────────────────────────────────

    public function test_dashboard_shows_what_needs_attention_upcoming_activity_and_one_tap_approval(): void
    {
        $p = $this->mkPost($this->noor, 'pending', ['title' => 'Autumn teaser']);
        $this->mkPost($this->noor, 'draft', ['title' => 'Hidden draft']);
        $p->events()->create(['actor' => 'Sara', 'role' => 'team', 'action' => 'submitted', 'round' => 1]);
        $p->comments()->create(['author' => 'Sara', 'role' => 'team', 'body' => 'Fresh from the studio']);

        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertOk()
            ->assertSee('Autumn teaser')->assertSee('Coming up')->assertSee('Recent activity')->assertSee('Sara')
            ->assertSee('/portal/posts/'.$p->id.'/approve', false)->assertDontSee('Hidden draft');

        $this->actingAs($this->noorUser)->post('/portal/posts/'.$p->id.'/approve')->assertRedirect();
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertSee('You are all caught up');
    }

    public function test_dashboard_compares_this_month_with_the_last(): void
    {
        $this->noor->reports()->create(['month' => '2026-08-01', 'reach' => 1000, 'engagement' => 3.0, 'follower_growth' => 10]);
        $this->noor->reports()->create(['month' => '2026-09-01', 'reach' => 1500, 'engagement' => 4.0, 'follower_growth' => 20, 'top_post' => 'Studio tour']);
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertSee('1,500')->assertSee('50%')->assertSee('1 pts');
    }

    public function test_clients_do_not_see_drafts(): void
    {
        $draft = $this->mkPost($this->noor, 'draft', ['title' => 'Secret draft']);
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id.'/content')->assertDontSee('Secret draft');
        $this->actingAs($this->noorUser)->get('/portal/posts/'.$draft->id)->assertNotFound();
        $this->actingAs($this->team)->get('/portal/c/'.$this->noor->id.'/content')->assertSee('Secret draft');
    }

    public function test_client_approval_is_timestamped_and_team_is_emailed(): void
    {
        $p = $this->mkPost($this->noor);
        $this->actingAs($this->noorUser)->post('/portal/posts/'.$p->id.'/approve')->assertRedirect();

        $this->assertSame('approved', $p->fresh()->status);
        $e = $p->events()->first();
        $this->assertSame('approved', $e->action);
        $this->assertSame($this->noorUser->name, $e->actor);
        $this->assertNotNull($e->created_at);
        Mail::assertQueued(PortalNotice::class, fn ($m) => $m->hasTo('admin@t.test') && str_contains($m->subjectLine, 'approved'));
        Mail::assertQueued(PortalNotice::class, fn ($m) => $m->hasTo('team@t.test'));
        Mail::assertNotQueued(PortalNotice::class, fn ($m) => $m->hasTo('omar@t.test')); // unassigned team member
    }

    public function test_staff_cannot_approve_on_behalf_and_only_pending_can_be_approved(): void
    {
        $p = $this->mkPost($this->noor);
        $this->actingAs($this->admin)->post('/portal/posts/'.$p->id.'/approve')->assertForbidden();
        $done = $this->mkPost($this->noor, 'published');
        $this->actingAs($this->noorUser)->post('/portal/posts/'.$done->id.'/approve')->assertStatus(422);
    }

    public function test_request_changes_needs_a_comment_and_rounds_count_up(): void
    {
        $p = $this->mkPost($this->noor, 'draft');
        $this->actingAs($this->admin)->post('/portal/posts/'.$p->id.'/status', ['do' => 'send']);
        $this->assertSame(1, $p->fresh()->round);

        $this->actingAs($this->noorUser)->post('/portal/posts/'.$p->id.'/changes', ['body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($this->noorUser)->post('/portal/posts/'.$p->id.'/changes', ['body' => 'Use the blue one'])->assertRedirect();
        $this->assertSame('changes', $p->fresh()->status);
        $this->assertSame('Use the blue one', $p->comments()->first()->body);
        Mail::assertQueued(PortalNotice::class, fn ($m) => str_contains($m->subjectLine, 'requested changes'));

        $this->actingAs($this->admin)->post('/portal/posts/'.$p->id.'/status', ['do' => 'send']);
        $p->refresh();
        $this->assertSame(2, $p->round);
        $this->assertSame('pending', $p->status);
    }

    public function test_comments_from_both_sides_and_client_comment_emails_team(): void
    {
        $p = $this->mkPost($this->noor);
        $this->actingAs($this->noorUser)->post('/portal/posts/'.$p->id.'/comments', ['body' => 'Love it'])->assertRedirect();
        $this->actingAs($this->team)->post('/portal/posts/'.$p->id.'/comments', ['body' => 'Thanks!'])->assertRedirect();
        $this->assertSame(['client', 'team'], $p->comments()->pluck('role')->all());
        Mail::assertQueued(PortalNotice::class, 2); // only the client comment notifies: the admin and the assigned team member
    }

    public function test_sending_a_batch_emails_the_client_and_creates_a_login_gated_link(): void
    {
        $a = $this->mkPost($this->noor, 'draft');
        $b = $this->mkPost($this->noor, 'draft');
        $res = $this->actingAs($this->team)->post('/portal/c/'.$this->noor->id.'/content/send', ['ids' => [$a->id, $b->id], 'title' => 'Week 44']);
        $res->assertRedirect()->assertSessionHas('batch');
        $this->assertSame('pending', $a->fresh()->status);
        Mail::assertQueued(PortalNotice::class, fn ($m) => $m->hasTo('noor@t.test') && str_contains($m->subjectLine, 'ready for your review'));

        $batch = $this->noor->batches()->first();
        auth()->logout();
        $this->get('/r/'.$batch->token)->assertRedirect('/login');
        $this->actingAs($this->noorUser)->get('/r/'.$batch->token)->assertOk()->assertSee('Week 44');
        $this->actingAs($this->atlasUser)->get('/r/'.$batch->token)->assertForbidden();
    }

    public function test_editing_an_approved_post_sends_it_back_to_draft(): void
    {
        $p = $this->mkPost($this->noor, 'approved');
        $this->actingAs($this->team)->put('/portal/posts/'.$p->id, ['title' => $p->title, 'platform' => 'instagram', 'caption' => 'New caption'])->assertRedirect();
        $this->assertSame('draft', $p->fresh()->status);
    }

    public function test_reel_links_are_restricted_to_known_hosts_and_types_are_derived(): void
    {
        $url = '/portal/c/'.$this->noor->id.'/content';
        $this->actingAs($this->team)->post($url, ['title' => 'Bad', 'platform' => 'instagram', 'video_url' => 'https://evil.example.com/x'])->assertSessionHasErrors('video_url');
        $this->actingAs($this->team)->post($url, ['title' => 'Bad', 'platform' => 'instagram', 'video_url' => 'javascript:alert(1)'])->assertSessionHasErrors('video_url');
        $this->actingAs($this->team)->post($url, ['title' => 'Reel', 'platform' => 'instagram', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'])->assertRedirect();
        $this->assertSame('reel', Post::where('title', 'Reel')->first()->type);
        $this->actingAs($this->team)->post($url, ['title' => 'Car', 'platform' => 'instagram', 'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]])->assertRedirect();
        $this->assertSame('carousel', Post::where('title', 'Car')->first()->type);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', Post::videoInfo('https://youtu.be/dQw4w9WgXcQ')['embed']);
        $this->assertNull(Post::videoInfo('http://youtu.be/dQw4w9WgXcQ')); // https only
    }

    public function test_media_is_private_and_images_only(): void
    {
        $p = $this->mkPost($this->noor);
        $this->get('/portal/posts/'.$p->id.'/media/0')->assertRedirect('/login');
        $this->actingAs($this->team)->post('/portal/c/'.$this->noor->id.'/content', ['title' => 'X', 'platform' => 'instagram', 'images' => [UploadedFile::fake()->create('evil.php', 5, 'text/x-php')]])->assertSessionHasErrors('images.0');
    }

    public function test_bulk_upload_creates_drafts_and_is_all_or_nothing(): void
    {
        $url = '/portal/c/'.$this->noor->id.'/content/bulk';
        $csv = UploadedFile::fake()->createWithContent('p.csv', "title,caption,platform,date,images,video_url\nOne,Hello,instagram,2026-11-03,a.jpg|b.jpg,\nTwo,Hi,instagram,2026-11-04,c.jpg,\n");
        $this->actingAs($this->team)->post($url, ['csv' => $csv, 'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg'), UploadedFile::fake()->image('extra_shot.jpg')]])->assertRedirect();
        $this->assertSame(3, $this->noor->posts()->count());
        $this->assertSame('carousel', $this->noor->posts()->where('title', 'One')->first()->type);
        $this->assertSame('draft', $this->noor->posts()->where('title', 'Two')->first()->status);
        $this->assertTrue($this->noor->posts()->where('title', 'Extra Shot')->exists());

        $bad = UploadedFile::fake()->createWithContent('bad.csv', "title,date,images\nGood,2026-11-03,a.jpg\nBad,not-a-date,missing.jpg\n");
        $this->actingAs($this->team)->post($url, ['csv' => $bad, 'images' => [UploadedFile::fake()->image('a.jpg')]])->assertSessionHasErrors('csv');
        $this->assertSame(3, $this->noor->posts()->count());
    }

    public function test_calendar_shows_posts_by_month_and_hides_drafts_from_clients(): void
    {
        $this->mkPost($this->noor, 'approved', ['title' => 'Cal Approved', 'scheduled_on' => '2026-11-10']);
        $this->mkPost($this->noor, 'draft', ['title' => 'Cal Draft', 'scheduled_on' => '2026-11-11']);
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id.'/calendar?month=2026-11')->assertOk()->assertSee('Cal Approved')->assertDontSee('Cal Draft')->assertSee('November 2026');
        $this->actingAs($this->team)->get('/portal/c/'.$this->noor->id.'/calendar?month=2026-11')->assertSee('Cal Draft');
    }

    public function test_pending_approvals_view_spans_assigned_clients(): void
    {
        $this->mkPost($this->noor, 'pending', ['title' => 'Noor pending']);
        $this->mkPost($this->atlas, 'pending', ['title' => 'Atlas pending']);
        $this->actingAs($this->admin)->get('/portal/admin')->assertSee('Noor pending')->assertSee('Atlas pending');
        $this->actingAs($this->team)->get('/portal/admin')->assertSee('Noor pending')->assertDontSee('Atlas pending');
    }

    // ── projects, files ──────────────────────────────────────

    public function test_projects_progress_and_permissions(): void
    {
        $this->actingAs($this->team)->post('/portal/c/'.$this->noor->id.'/projects', ['name' => 'October Content'])->assertRedirect();
        $p = $this->noor->projects()->first();
        $this->actingAs($this->team)->post('/portal/projects/'.$p->id.'/tasks', ['title' => 'A', 'due_on' => '2026-11-01']);
        $this->actingAs($this->team)->post('/portal/projects/'.$p->id.'/tasks', ['title' => 'B']);
        $t = $p->tasks()->first();
        $this->actingAs($this->team)->put('/portal/tasks/'.$t->id, ['status' => 'done']);
        $this->assertSame(50, $p->fresh()->load('tasks')->progress());
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id.'/projects')->assertSee('October Content')->assertSee('50%');
        $this->actingAs($this->noorUser)->post('/portal/c/'.$this->noor->id.'/projects', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($this->noorUser)->put('/portal/tasks/'.$t->id, ['status' => 'todo'])->assertForbidden();
    }

    public function test_files_client_uploads_only_to_their_folder_and_whitelist_applies(): void
    {
        $url = '/portal/c/'.$this->noor->id.'/files';
        $this->actingAs($this->noorUser)->post($url, ['folder' => 'brand', 'files' => [UploadedFile::fake()->create('photo.jpg', 20, 'image/jpeg')]])->assertRedirect();
        $f = $this->noor->files()->first();
        $this->assertSame('client', $f->folder);
        $this->assertSame('client', $f->uploader_role);

        $this->actingAs($this->noorUser)->post($url, ['files' => [UploadedFile::fake()->create('run.exe', 20)]])->assertSessionHasErrors('files');
        $this->actingAs($this->noorUser)->post($url, ['files' => [UploadedFile::fake()->create('page.html', 20, 'text/html')]])->assertSessionHasErrors('files');
        $this->assertSame(1, $this->noor->files()->count());

        $this->actingAs($this->team)->post($url, ['folder' => 'brand', 'files' => [UploadedFile::fake()->create('kit.pdf', 20, 'application/pdf')]])->assertRedirect();
        $this->assertSame('brand', $this->noor->files()->where('name', 'kit.pdf')->first()->folder);

        $this->actingAs($this->noorUser)->get('/portal/files/'.$f->id)->assertOk();
        $this->actingAs($this->atlasUser)->get('/portal/files/'.$f->id)->assertForbidden();
        $brand = $this->noor->files()->where('folder', 'brand')->first();
        $this->actingAs($this->noorUser)->delete('/portal/files/'.$brand->id)->assertForbidden(); // clients can't delete team files
        $this->actingAs($this->noorUser)->delete('/portal/files/'.$f->id)->assertRedirect();
    }

    // ── invoices ─────────────────────────────────────────────

    public function test_invoice_status_is_computed_from_dates(): void
    {
        $mk = fn ($due, $paid = null) => new Invoice(['due_on' => $due, 'paid_on' => $paid]);
        $this->assertSame('pending', $mk(today()->addDays(10))->status());
        $this->assertSame('due_soon', $mk(today()->addDays(3))->status());
        $this->assertSame('due_soon', $mk(today())->status());
        $this->assertSame('overdue', $mk(today()->subDay())->status());
        $this->assertSame('paid', $mk(today()->subDays(30), today())->status());
    }

    public function test_admin_manages_invoices_client_views_and_downloads_pdf(): void
    {
        $this->actingAs($this->admin)->post('/portal/c/'.$this->noor->id.'/invoices', ['action' => 'send', 'items' => [['description' => 'Retainer', 'qty' => 1, 'unit_price' => 1500]], 'discount_type' => 'fixed', 'currency' => 'AED', 'issued_on' => today()->toDateString(), 'due_on' => today()->addDays(14)->toDateString(), 'title' => 'Retainer'])->assertRedirect();
        $i = $this->noor->invoices()->first();
        $this->assertStringStartsWith('INV-', $i->number);

        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id.'/invoices')->assertSee($i->number)->assertSee('Pending');
        $this->actingAs($this->noorUser)->post('/portal/c/'.$this->noor->id.'/invoices', ['items' => [['description' => 'x', 'qty' => 1, 'unit_price' => 1]], 'discount_type' => 'fixed', 'currency' => 'AED', 'issued_on' => today()->toDateString(), 'due_on' => today()->toDateString()])->assertForbidden();
        $this->actingAs($this->noorUser)->post('/portal/invoices/'.$i->id.'/paid')->assertForbidden();
        $this->actingAs($this->team)->post('/portal/invoices/'.$i->id.'/paid')->assertForbidden(); // Editor lacks billing.manage

        $res = $this->actingAs($this->noorUser)->get('/portal/invoices/'.$i->id.'/pdf');
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));

        $this->actingAs($this->admin)->post('/portal/invoices/'.$i->id.'/paid')->assertRedirect();
        $this->assertSame('paid', $i->fresh()->status());
        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id.'/invoices')->assertSee('Payment history');
    }

    public function test_invoice_reminders_go_out_once_before_and_repeat_when_overdue(): void
    {
        $soon = $this->noor->invoices()->create(['number' => 'S-1', 'amount' => 10, 'currency' => 'AED', 'issued_on' => today()->subDays(5), 'due_on' => today()->addDays(2)]);
        $late = $this->noor->invoices()->create(['number' => 'L-1', 'amount' => 10, 'currency' => 'AED', 'issued_on' => today()->subDays(30), 'due_on' => today()->subDays(3)]);
        $far = $this->noor->invoices()->create(['number' => 'F-1', 'amount' => 10, 'currency' => 'AED', 'issued_on' => today(), 'due_on' => today()->addDays(30)]);
        $paid = $this->noor->invoices()->create(['number' => 'P-1', 'amount' => 10, 'currency' => 'AED', 'issued_on' => today()->subDays(30), 'due_on' => today()->subDays(3), 'paid_on' => today()]);

        $this->artisan('portal:invoice-reminders')->assertSuccessful();
        Mail::assertQueued(PortalNotice::class, 2);
        Mail::assertQueued(PortalNotice::class, fn ($m) => str_contains($m->subjectLine, 'Reminder: invoice S-1'));
        Mail::assertQueued(PortalNotice::class, fn ($m) => str_contains($m->subjectLine, 'Overdue: invoice L-1'));

        $this->artisan('portal:invoice-reminders'); // same day: nothing new
        Mail::assertQueued(PortalNotice::class, 2);

        $this->travel(8)->days();
        $this->artisan('portal:invoice-reminders');
        Mail::assertQueued(PortalNotice::class, fn ($m) => str_contains($m->subjectLine, 'Overdue: invoice S-1')); // now overdue too
        $this->assertNull($far->fresh()->reminded_overdue_at);
        $this->assertNull($paid->fresh()->reminded_before_at);
    }

    // ── reports ──────────────────────────────────────────────

    public function test_reports_are_entered_by_team_emailed_and_shown_on_dashboard(): void
    {
        $this->actingAs($this->team)->post('/portal/c/'.$this->noor->id.'/reports', [
            'month' => '2026-09', 'reach' => 48200, 'engagement' => 4.8, 'follower_growth' => 310, 'top_post' => 'Studio tour',
            'file' => UploadedFile::fake()->create('sep.pdf', 30, 'application/pdf'),
        ])->assertRedirect();
        $r = Report::first();
        $this->assertTrue($r->month->isSameDay('2026-09-01'));
        Mail::assertQueued(PortalNotice::class, fn ($m) => $m->hasTo('noor@t.test') && str_contains($m->subjectLine, 'September 2026'));

        $this->actingAs($this->noorUser)->get('/portal/c/'.$this->noor->id)->assertSee('48,200')->assertSee('Studio tour')->assertSee('+310');
        $this->actingAs($this->noorUser)->get('/portal/reports/'.$r->id.'/pdf')->assertOk();
        $this->actingAs($this->atlasUser)->get('/portal/reports/'.$r->id.'/pdf')->assertForbidden();
        $this->actingAs($this->noorUser)->post('/portal/c/'.$this->noor->id.'/reports', ['month' => '2026-10', 'reach' => 1, 'engagement' => 1, 'follower_growth' => 1])->assertForbidden();

        // same month again updates instead of duplicating
        $this->actingAs($this->team)->post('/portal/c/'.$this->noor->id.'/reports', ['month' => '2026-09', 'reach' => 50000, 'engagement' => 5, 'follower_growth' => 320]);
        $this->assertSame(1, Report::count());
        $this->assertSame(50000, $r->fresh()->reach);
    }

    // ── admin: clients and logins ────────────────────────────

    public function test_admin_creates_a_client_with_login_and_team_assignment(): void
    {
        $this->actingAs($this->admin)->post('/portal/clients', [
            'name' => 'Rosa Cafe', 'currency' => 'AED', 'client_name' => 'Rosa', 'client_email' => 'rosa@t.test', 'client_password' => 'longpassword', 'team' => [$this->otherTeam->id],
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])->assertRedirect()->assertSessionHas('creds');
        $a = Account::where('name', 'Rosa Cafe')->first();
        $this->assertNotNull($a->logo);
        $this->assertSame('guest', User::where('email', 'rosa@t.test')->first()->role);
        $this->assertTrue($a->team->contains($this->otherTeam));
        $this->post('/login', ['email' => 'rosa@t.test', 'password' => 'longpassword'])->assertRedirect('/portal');
        $this->actingAs($this->admin)->get('/portal/c/'.$a->id.'/logo')->assertOk();
    }

    public function test_team_member_without_all_clients_cannot_create_or_delete_clients(): void
    {
        $this->actingAs($this->team)->post('/portal/clients', ['name' => 'X', 'client_name' => 'X', 'client_email' => 'x@t.test'])->assertForbidden();
        $this->actingAs($this->team)->delete('/portal/clients/'.$this->noor->id)->assertForbidden();
        $this->actingAs($this->noorUser)->get('/portal/clients/'.$this->noor->id)->assertForbidden();
    }

    // ── login & passwords ────────────────────────────────────

    public function test_forgot_password_flow(): void
    {
        Notification::fake();
        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => 'noor@t.test'])->assertSessionHas('status');
        Notification::assertSentTo($this->noorUser, \Illuminate\Auth\Notifications\ResetPassword::class);
        // an unknown address gets the exact same answer
        $this->post('/forgot-password', ['email' => 'nobody@t.test'])->assertSessionHas('status');
        Notification::assertCount(1);
    }

    public function test_reset_password_changes_it(): void
    {
        $token = \Illuminate\Support\Facades\Password::createToken($this->noorUser);
        $this->get('/reset-password/'.$token.'?email=noor@t.test')->assertOk();
        $this->post('/reset-password', ['token' => $token, 'email' => 'noor@t.test', 'password' => 'brandnewpass', 'password_confirmation' => 'brandnewpass'])->assertRedirect('/login');
        $this->post('/login', ['email' => 'noor@t.test', 'password' => 'brandnewpass'])->assertRedirect('/portal');
    }

    public function test_login_returns_to_the_whatsapp_link_after_signing_in(): void
    {
        $batch = $this->noor->batches()->create(['token' => Str::random(40), 'title' => 'B', 'sent_at' => now()]);
        $this->get('/r/'.$batch->token)->assertRedirect('/login');
        $this->post('/login', ['email' => 'noor@t.test', 'password' => 'secret123'])->assertRedirect('/r/'.$batch->token);
    }

    public function test_pages_render_for_each_role(): void
    {
        $p = $this->mkPost($this->noor);
        $pages = ['', '/content', '/calendar', '/projects', '/files', '/invoices', '/reports'];
        foreach ([$this->noorUser, $this->team, $this->admin] as $u) {
            foreach ($pages as $page) {
                $this->actingAs($u)->get('/portal/c/'.$this->noor->id.$page)->assertOk();
            }
            $this->actingAs($u)->get('/portal/posts/'.$p->id)->assertOk();
        }
        foreach (['/portal/c/'.$this->noor->id.'/content/new', '/portal/c/'.$this->noor->id.'/content/bulk', '/portal/posts/'.$p->id.'/edit', '/portal/admin', '/portal/clients', '/portal/clients/create', '/portal/clients/'.$this->noor->id, '/portal/bulk-template.csv'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }
}
