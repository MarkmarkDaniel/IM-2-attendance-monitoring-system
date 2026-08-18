<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * DEVELOPMENT / DEMO ACCOUNT ONLY.
     * Change this password before using the system for real data.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'System Administrator',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_ADMIN,
            ]
        );
    }
}
