<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Agency;
use App\Models\Role;
use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SidebarClientsTeamTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->agency = Agency::create(['name' => 'Demo Agency', 'brand_color' => '#111111', 'max_workspaces' => 50, 'max_members' => 20]);
        Role::ensureDefaults($this->agency);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@t.test', 'password' => 'secret123', 'role' => 'owner', 'agency_id' => $this->agency->id]);
    }

    private function client(string $name, string $when): Account
    {
        $a = Account::create(['agency_id' => $this->agency->id, 'name' => $name, 'token' => Str::random(24), 'share_enabled' => false]);
        Account::whereKey($a->id)->update(['updated_at' => $when]);

        return $a->refresh();
    }

    public function test_clients_with_the_latest_update_come_first(): void
    {
        $old = $this->client('Old Co', now()->subDays(5));
        $mid = $this->client('Mid Co', now()->subDays(2));
        $new = $this->client('New Co', now()->subDays(9));
        $this->assertSame(['Mid Co', 'Old Co', 'New Co'], Access::accountsFor($this->admin)->pluck('name')->all());

        // activity anywhere in a client (here: a project) moves it to the top
        $new->projects()->create(['name' => 'Launch', 'status' => 'active']);
        $this->assertSame('New Co', Access::accountsFor($this->admin)->first()->name);
    }

    public function test_sidebar_shows_agency_and_client_logos(): void
    {
        $a = $this->client('Logo Co', now());
        $a->update(['logo' => 'logos/'.$a->id.'/l.png']);
        $this->agency->update(['logo' => 'agency-logos/'.$this->agency->id.'/l.png']);
        $this->actingAs($this->admin)->get('/portal/clients')->assertOk()
            ->assertSee('/agency-logo/'.$this->agency->id, false)->assertSee('/portal/c/'.$a->id.'/logo', false);
    }

    public function test_agency_logo_upload_and_download(): void
    {
        $this->actingAs($this->admin)->post('/api/agency/logo', ['logo' => UploadedFile::fake()->image('l.png', 80, 80)])->assertOk()->assertJsonStructure(['logo']);
        $this->agency->refresh();
        $this->assertNotNull($this->agency->logo);
        $this->get('/agency-logo/'.$this->agency->id)->assertOk();
        $this->delete('/api/agency/logo')->assertOk();
        $this->assertNull($this->agency->refresh()->logo);
    }

    public function test_clients_page_filters_and_views(): void
    {
        $a = $this->client('Alpha Studio', now());
        $this->client('Beta Works', now()->subDay());
        $a->posts()->create(['title' => 'P', 'platform' => 'instagram', 'status' => 'pending', 'media' => [], 'round' => 1]);
        $this->actingAs($this->admin);
        $this->get('/portal/clients')->assertOk()->assertSee('Alpha Studio')->assertSee('Beta Works');
        $this->get('/portal/clients?status=pending')->assertOk()->assertViewHas('accounts', fn ($p) => $p->pluck('name')->all() === ['Alpha Studio']);
        $this->get('/portal/clients?q=beta&view=cards')->assertOk()->assertViewHas('accounts', fn ($p) => $p->pluck('name')->all() === ['Beta Works']);
        $this->get('/portal/clients?order=name:desc&team='.$this->admin->id.'&from=2020-01-01&to=2099-01-01')->assertOk();
    }

    public function test_team_page_filters_and_views(): void
    {
        $editor = Role::where('agency_id', $this->agency->id)->where('name', 'Editor')->first();
        User::create(['name' => 'Sara Member', 'email' => 's@t.test', 'password' => 'secret123', 'role' => 'member', 'agency_id' => $this->agency->id, 'role_id' => $editor->id]);
        $c = $this->client('Alpha Studio', now());
        $this->actingAs($this->admin);
        $this->get('/team')->assertOk()->assertSee('Sara Member');
        $this->get('/team?type=owner')->assertOk()->assertViewHas('members', fn ($p) => $p->pluck('name')->all() === ['Admin']);
        $this->get('/team?view=cards&type=member&order=clients:desc&client='.$c->id.'&from=2020-01-01&to=2099-01-01')->assertOk();
    }

    public function test_add_buttons_open_modals_on_portal_pages(): void
    {
        $a = $this->client('Alpha Studio', now());
        $this->actingAs($this->admin);
        foreach (['projects' => 'addProject', 'files' => 'uploadFiles', 'reports' => 'addReport'] as $page => $modal) {
            $this->get("/portal/c/{$a->id}/$page")->assertOk()->assertSee('data-modal="'.$modal.'"', false)->assertSee('id="'.$modal.'"', false);
        }
        $this->get("/portal/c/{$a->id}/calendar")->assertOk()->assertSee('New post');
        $this->get("/portal/c/{$a->id}/content/new")->assertOk()->assertSee('data-fin', false);
        $this->get('/portal/clients/'.$a->id)->assertOk()->assertSee('data-imgin', false);
    }
}
