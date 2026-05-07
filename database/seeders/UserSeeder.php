<?php

namespace Database\Seeders;

use App\Models\Role;
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
            ['name' => 'Admin User', 'email' => 'admin@restaurant.com', 'phone' => '0901234567', 'role' => 'admin'],
            ['name' => 'Manager User', 'email' => 'manager@restaurant.com', 'phone' => '0901234568', 'role' => 'manager'],
            ['name' => 'Staff User', 'email' => 'staff@restaurant.com', 'phone' => '0901234569', 'role' => 'staff'],
            ['name' => 'Chef User', 'email' => 'chef@restaurant.com', 'phone' => '0901234570', 'role' => 'chef'],
            ['name' => 'Customer User', 'email' => 'customer@restaurant.com', 'phone' => '0901234571', 'role' => 'customer'],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $userData['phone'],
                    'role' => $userData['role'],
                ]
            );

            $role = Role::where('name', $userData['role'])->first();

            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }
    }
}
