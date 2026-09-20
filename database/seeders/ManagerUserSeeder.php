<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ManagerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('MANAGER_EMAIL', 'manager@hometech.com');
        $password = env('MANAGER_PASSWORD');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Cannot seed the manager account: MANAGER_PASSWORD is not set in .env.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Manager',
                'password' => $password,
                'role' => UserRole::Manager,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
