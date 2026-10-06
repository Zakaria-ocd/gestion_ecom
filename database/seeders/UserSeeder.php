<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'demo-admin',
                'email' => 'admin@example.com',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            [
                'username' => 'demo-buyer',
                'email' => 'buyer@example.com',
                'password' => 'password123',
                'role' => 'buyer',
            ],
            [
                'username' => 'demo-seller',
                'email' => 'seller@example.com',
                'password' => 'seller123',
                'role' => 'seller',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'username' => $user['username'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'image' => null,
                ]
            );
        }
    }
}
