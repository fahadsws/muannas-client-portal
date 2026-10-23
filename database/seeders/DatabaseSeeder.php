<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Only the platform super admin is created — everything else is entered by hand. */
    public function run(): void
    {
        User::firstOrCreate(['email' => config('board.admin_email')], [
            'name' => 'Platform Admin',
            'password' => config('board.admin_password'),
            'role' => 'super',
        ]);

        if (config('portal.admin_email') && config('portal.admin_password')) {
            $this->call(MuannasSeeder::class);
        }
    }
}
