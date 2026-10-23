<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\DocTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production setup: creates the Muannas Atelier agency and its first admin.
 *   PORTAL_ADMIN_EMAIL / PORTAL_ADMIN_PASSWORD must be set in .env first.
 * Safe to run twice.
 */
class MuannasSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('portal.admin_email');
        $password = config('portal.admin_password');
        if (! $email || ! $password) {
            $this->command?->error('Set PORTAL_ADMIN_EMAIL and PORTAL_ADMIN_PASSWORD in .env first.');

            return;
        }

        $agency = Agency::firstOrCreate(['name' => config('portal.name')], ['brand_color' => '#0f1e3d', 'max_workspaces' => 200, 'max_members' => 50]);
        if ($agency->templates()->doesntExist()) {
            foreach (DocTemplate::defaults() as $t) {
                $agency->templates()->create($t);
            }
        }
        Role::ensureDefaults($agency);

        $admin = User::firstOrNew(['email' => $email]);
        if (! $admin->exists) {
            $admin->fill(['name' => config('portal.admin_name'), 'password' => $password, 'role' => 'owner', 'agency_id' => $agency->id])->save();
            Role::ensureDefaults($agency);
        }
        $this->command?->info("Agency \"{$agency->name}\" ready. Admin: {$email}");
    }
}
