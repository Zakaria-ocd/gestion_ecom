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
        // Create a test buyer user
        User::create([
            'username' => 'testbuyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'buyer',
            'image' => 'default.jpg'
        ]);
        
        // Create a test admin user
        User::create([
            'username' => 'testadmin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'image' => 'default.jpg'
        ]);
    }
} 