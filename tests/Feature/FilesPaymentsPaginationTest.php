<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Agency;
use App\Models\ClientFile;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FilesPaymentsPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $client;

    private Account $acct;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->agency = Agency::create(['name' => 'Demo Agency', 'brand_color' => '#111111', 'max_workspaces' => 50, 'max_members' => 20]);
        Role::ensureDefaults($this->agency);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.test', 'password' => 'secret123', 'role' => 'owner', 'agency_id' => $this->agency->id]);
        $this->acct = Account::create(['agency_id' => $this->agency->id, 'name' => 'Noor', 'token' => Str::random(24), 'share_enabled' => false]);
        $this->client = User::create(['name' => 'Noor Owner', 'email' => 'noor@t.test', 'password' => 'secret123', 'role' => 'guest']);
        $this->acct->people()->attach($this->client->id, ['perm' => 'comment']);
    }

    public function test_team_creates_a_folder_uploads_into_it_and_files_paginate(): void
    {
        $base = '/portal/c/'.$this->acct->id;
        $this->actingAs($this->admin)->post($base.'/file-folders', ['name' => 'Campaign photos'])->assertRedirect();
        $folder = $this->acct->fileFolders()->first();
        $this->post($base.'/file-folders', ['name' => 'Campaign photos'])->assertSessionHasErrors('name');
        $this->post($base.'/file-folders', ['name' => 'Logos'])->assertSessionHasErrors('name'); // clashes with a built-in folder

        foreach (range(1, 22) as $n) {
            $this->post($base.'/files', ['folder' => $folder->key(), 'files' => [UploadedFile::fake()->image("p{$n}.jpg")]])->assertRedirect();
        }
        $this->assertSame(22, $this->acct->files()->where('folder', $folder->key())->count());
        $this->get($base.'/files')->assertOk()->assertSee('Campaign photos')->assertSee('22 files');
        $this->get($base.'/files/folder/'.$folder->key())->assertOk()->assertSee('Showing')->assertSee('1–20');
        $this->get($base.'/files/folder/'.$folder->key().'?page=2')->assertOk()->assertSee('21–22');
        $this->get($base.'/files/folder/c999')->assertNotFound();

        // not empty: cannot be deleted; rename works
        $this->delete('/portal/file-folders/'.$folder->id)->assertSessionHas('err');
        $this->put('/portal/file-folders/'.$folder->id, ['name' => 'Photos'])->assertRedirect();
        $this->assertSame('Photos', $folder->fresh()->name);

        // clients see it and cannot add to it or manage folders
        $this->actingAs($this->client)->get($base.'/files')->assertOk()->assertSee('Photos');
        $this->actingAs($this->client)->post($base.'/file-folders', ['name' => 'X'])->assertForbidden();
        $this->actingAs($this->client)->post($base.'/files', ['folder' => $folder->key(), 'files' => [UploadedFile::fake()->image('x.jpg')]])->assertRedirect();
        $this->assertSame(0, $this->acct->files()->where('folder', $folder->key())->where('uploader_role', 'client')->count());
    }

    public function test_report_pdf_lands_in_the_reports_folder_and_goes_with_the_report(): void
    {
        $base = '/portal/c/'.$this->acct->id;
        $this->actingAs($this->admin)->post($base.'/reports', ['month' => '2026-09', 'reach' => 100, 'engagement' => 2.5, 'follower_growth' => 10, 'file' => UploadedFile::fake()->create('r.pdf', 20, 'application/pdf')])->assertRedirect();
        $file = ClientFile::where('folder', 'reports')->first();
        $this->assertNotNull($file);
        $this->assertSame('report', $file->ref_type);
        $this->get($base.'/files/folder/reports')->assertOk()->assertSee('Performance report 2026-09.pdf');
        $this->delete('/portal/files/'.$file->id)->assertSessionHas('err'); // owned by the report
        $this->assertTrue(ClientFile::whereKey($file->id)->exists());

        $this->delete('/portal/reports/'.Report::first()->id)->assertRedirect();
        $this->assertSame(0, ClientFile::where('folder', 'reports')->count());
    }

    public function test_payment_history_is_a_paged_timeline(): void
    {
        $this->actingAs($this->admin);
        foreach (range(1, 17) as $n) {
            $inv = $this->acct->invoices()->create(['number' => 'P-'.$n, 'amount' => 100, 'currency' => 'AED', 'issued_on' => today()->subMonths($n), 'due_on' => today()->subMonths($n)->addDays(10), 'paid_on' => today()->subMonths($n)->addDays(5), 'draft' => false]);
        }
        $this->acct->invoices()->create(['number' => 'D-1', 'amount' => 5, 'currency' => 'AED', 'issued_on' => today(), 'due_on' => today(), 'paid_on' => today(), 'draft' => true]);
        $base = '/portal/c/'.$this->acct->id;
        $this->get($base.'/payments')->assertOk()->assertSee('Payment history')->assertSee('1–15')->assertSee('of <b>17</b>', false)->assertDontSee('D-1');
        $this->get($base.'/payments?page=2')->assertOk()->assertSee('16–17');
        $this->actingAs($this->client)->get($base.'/payments')->assertOk();
        $this->get($base.'/invoices')->assertOk()->assertSee('View full history');
    }

    public function test_content_review_and_invoices_paginate(): void
    {
        foreach (range(1, 26) as $n) {
            $this->acct->posts()->create(['title' => 'Post '.$n, 'platform' => 'instagram', 'status' => 'published', 'media' => [], 'round' => 1]);
        }
        foreach (range(1, 12) as $n) {
            $this->acct->invoices()->create(['number' => 'I-'.$n, 'amount' => 10, 'currency' => 'AED', 'issued_on' => today(), 'due_on' => today()->addDays(30), 'draft' => false]);
        }
        $this->actingAs($this->admin);
        $this->get('/portal/c/'.$this->acct->id.'/content')->assertOk()->assertSee('1–24')->assertSee('of <b>26</b>', false);
        $this->get('/portal/c/'.$this->acct->id.'/content?page=2')->assertOk()->assertSee('25–26');
        $this->get('/portal/c/'.$this->acct->id.'/invoices')->assertOk()->assertSee('1–10')->assertSee('of <b>12</b>', false);
    }
}
