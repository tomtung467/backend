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
            ['name' => 'Cashier User', 'email' => 'cashier@restaurant.com', 'phone' => '0901234572', 'role' => 'staff', 'pivot_role' => 'cashier'],
            ['name' => 'Host User', 'email' => 'host@restaurant.com', 'phone' => '0901234573', 'role' => 'staff', 'pivot_role' => 'host'],
            ['name' => 'Bartender User', 'email' => 'bartender@restaurant.com', 'phone' => '0901234574', 'role' => 'staff', 'pivot_role' => 'bartender'],
            ['name' => 'Inventory User', 'email' => 'inventory@restaurant.com', 'phone' => '0901234575', 'role' => 'staff', 'pivot_role' => 'inventory'],
            ['name' => 'Accountant User', 'email' => 'accountant@restaurant.com', 'phone' => '0901234576', 'role' => 'manager', 'pivot_role' => 'accountant'],
            ['name' => 'Supervisor User', 'email' => 'supervisor@restaurant.com', 'phone' => '0901234577', 'role' => 'manager', 'pivot_role' => 'supervisor'],
            ['name' => 'Customer Two', 'email' => 'customer2@restaurant.com', 'phone' => '0901234578', 'role' => 'customer'],
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

            $role = Role::where('name', $userData['pivot_role'] ?? $userData['role'])->first();

            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }
    }
}
