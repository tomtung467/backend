<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@restaurant.test',
            'password' => Hash::make('password123'),
            'phone' => '0901234567',
            'role' => 'admin',
        ]);

        // Manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@restaurant.test',
            'password' => Hash::make('password123'),
            'phone' => '0901234568',
            'role' => 'manager',
        ]);

        // Staff user
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@restaurant.test',
            'password' => Hash::make('password123'),
            'phone' => '0901234569',
            'role' => 'staff',
        ]);

        // Chef user
        User::create([
            'name' => 'Chef User',
            'email' => 'chef@restaurant.test',
            'password' => Hash::make('password123'),
            'phone' => '0901234570',
            'role' => 'chef',
        ]);

        // Customer user
        User::create([
            'name' => 'Customer User',
            'email' => 'customer@restaurant.test',
            'password' => Hash::make('password123'),
            'phone' => '0901234571',
            'role' => 'customer',
        ]);
    }
}
