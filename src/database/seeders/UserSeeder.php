<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin User',
                'email'    => 'admin@bet.com',
                'password' => 'password',
                'role'     => UserRole::Admin,
            ],
            [
                'name'     => 'Manager User',
                'email'    => 'manager@bet.com',
                'password' => 'password',
                'role'     => UserRole::Manager,
            ],
            [
                'name'     => 'Finance User',
                'email'    => 'finance@bet.com',
                'password' => 'password',
                'role'     => UserRole::Finance,
            ],
            [
                'name'     => 'Regular User',
                'email'    => 'user@bet.com',
                'password' => 'password',
                'role'     => UserRole::User,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData,
            );
        }
    }
}
