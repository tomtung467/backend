<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Food;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Coupon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator']
        );
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Manager']
        );
        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            ['description' => 'Staff Member']
        );
        $chefRole = Role::firstOrCreate(
            ['name' => 'chef'],
            ['description' => 'Chef']
        );
        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['description' => 'Customer']
        );

        // Create admin user
        $adminUser = User::firstOrCreate([
            'email' => 'admin@restaurant.com',
        ], [
            'name' => 'Admin User',
            'password' => bcrypt('password'),
        ]);
        $adminUser->roles()->sync([$adminRole->id]);

        // Create manager user
        $managerUser = User::firstOrCreate([
            'email' => 'manager@restaurant.com',
        ], [
            'name' => 'Manager User',
            'password' => bcrypt('password'),
        ]);
        $managerUser->roles()->sync([$managerRole->id]);

        // Create staff user
        $staffUser = User::firstOrCreate([
            'email' => 'staff@restaurant.com',
        ], [
            'name' => 'Staff User',
            'password' => bcrypt('password'),
        ]);
        $staffUser->roles()->sync([$staffRole->id]);

        // Create chef user
        $chefUser = User::firstOrCreate([
            'email' => 'chef@restaurant.com',
        ], [
            'name' => 'Chef User',
            'password' => bcrypt('password'),
        ]);
        $chefUser->roles()->sync([$chefRole->id]);

        // Create customer user
        $customerUser = User::firstOrCreate([
            'email' => 'customer@restaurant.com',
        ], [
            'name' => 'Customer User',
            'password' => bcrypt('password'),
        ]);
        $customerUser->roles()->sync([$customerRole->id]);

        // Create departments
        $kitchenDept = Department::firstOrCreate([
            'name' => 'Kitchen',
            'description' => 'Kitchen Department',
        ]);

        $serviceDept = Department::firstOrCreate([
            'name' => 'Service',
            'description' => 'Customer Service Department',
        ]);

        // Create employees
        Employee::firstOrCreate([
            'user_id' => $chefUser->id,
        ], [
            'first_name' => 'Chef',
            'last_name' => 'User',
            'employee_id_number' => 'EMP001',
            'department_id' => $kitchenDept->id,
            'position' => 'Head Chef',
            'hire_date' => now(),
            'salary' => 5000,
            'status' => 'active',
        ]);

        Employee::firstOrCreate([
            'user_id' => $staffUser->id,
        ], [
            'first_name' => 'Staff',
            'last_name' => 'User',
            'employee_id_number' => 'EMP002',
            'department_id' => $serviceDept->id,
            'position' => 'Waiter',
            'hire_date' => now(),
            'salary' => 2500,
            'status' => 'active',
        ]);

        // Create categories
        $appetizers = Category::firstOrCreate([
            'name' => 'Appetizers',
            'description' => 'Appetizers and starters',
            'is_active' => true,
        ]);

        $mains = Category::firstOrCreate([
            'name' => 'Main Courses',
            'description' => 'Main dishes',
            'is_active' => true,
        ]);

        $desserts = Category::firstOrCreate([
            'name' => 'Desserts',
            'description' => 'Sweet desserts',
            'is_active' => true,
        ]);

        // Create sample foods
        Food::firstOrCreate([
            'name' => 'Caesar Salad',
            'category_id' => $appetizers->id,
        ], [
            'description' => 'Fresh Caesar salad with croutons',
            'price' => 45000,
            'is_available' => true,
        ]);

        Food::firstOrCreate([
            'name' => 'Grilled Chicken',
            'category_id' => $mains->id,
        ], [
            'description' => 'Grilled chicken with vegetables',
            'price' => 120000,
            'is_available' => true,
        ]);

        Food::firstOrCreate([
            'name' => 'Chocolate Cake',
            'category_id' => $desserts->id,
        ], [
            'description' => 'Delicious chocolate cake',
            'price' => 35000,
            'is_available' => true,
        ]);

        // Create tables
        for ($i = 1; $i <= 10; $i++) {
            Table::firstOrCreate([
                'table_number' => $i,
            ], [
                'capacity' => 4,
                'status' => 'empty',
            ]);
        }
    }
}

