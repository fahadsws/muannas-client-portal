<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Only the platform super admin is created — everything else is entered by hand. */
    public function run(): void
    {
        User::create([
            'name' => 'Platform Admin',
            'email' => config('board.admin_email'),
            'password' => config('board.admin_password'),
            'role' => 'super',
        ]);
    }
}
