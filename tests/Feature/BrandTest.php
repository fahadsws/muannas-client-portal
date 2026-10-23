<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_name_logo_and_admin_upload(): void
    {
        Storage::fake('local');
        $this->assertSame('Atelo', config('board.brand'));
        $this->get('/login')->assertOk()->assertSee('Atelo')->assertSee('/brand/favicon', false);
        $this->get('/brand/logo')->assertOk();
        $this->get('/brand/nope')->assertNotFound();

        $super = User::create(['name' => 'Root', 'email' => 'r@t.test', 'password' => 'secret123', 'role' => 'super']);
        $this->actingAs($super)->post('/admin/settings', [
            'group' => 'platform', 'brand' => 'Atelo', 'tagline' => 'Client OS', 'default_max_workspaces' => 10, 'default_max_members' => 5,
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200), 'favicon' => UploadedFile::fake()->image('fav.png', 64, 64),
        ])->assertRedirect();
        $this->assertNotSame('', Setting::all_()['logo_path']);
        $this->get('/brand/logo')->assertOk();
        $this->get('/admin/settings')->assertOk()->assertSee('Platform logo');

        $this->post('/admin/settings', ['group' => 'platform', 'brand' => 'Atelo', 'tagline' => 'Client OS', 'default_max_workspaces' => 10, 'default_max_members' => 5, 'remove_logo' => 1])->assertRedirect();
        $this->assertSame('', Setting::all_()['logo_path']);
    }
}
