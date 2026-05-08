<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Food;
use App\Models\Ingredient;
use App\Models\InventoryLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Role;
use App\Models\Table;
use App\Models\TableReservation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Model::unguarded(function () {
            $roles = $this->seedRoles();

            $this->call(UserSeeder::class);

            $permissions = $this->seedPermissions();
            $roles['admin']->permissions()->sync($permissions->pluck('id')->all());
            $roles['manager']->permissions()->sync($permissions->pluck('id')->take(8)->all());
            $roles['staff']->permissions()->sync($permissions->whereIn('name', ['orders.view', 'orders.create', 'tables.manage'])->pluck('id')->all());
            $roles['chef']->permissions()->sync($permissions->whereIn('name', ['orders.view', 'kitchen.manage', 'foods.view'])->pluck('id')->all());

            $departments = $this->seedDepartments();
            $employees = $this->seedEmployees($departments);
            $this->assignDepartmentManagers($departments, $employees);

            $categories = $this->seedCategories();
            $foods = $this->seedFoods($categories);
            $ingredients = $this->seedIngredients();
            $recipes = $this->seedRecipes($foods);
            $this->seedRecipeItems($recipes, $ingredients);

            $tables = $this->seedTables();
            $coupons = $this->seedCoupons($employees);
            $this->seedTableReservations($tables, $foods);
            $orders = $this->seedOrders($tables, $foods, $employees, $coupons);
            $payments = $this->seedPayments($orders, $employees);
            $this->seedInvoices($payments);
            $this->seedInventoryLogs($ingredients, $employees);
            $this->seedAuditLogs();
        });
    }

    private function seedRoles(): array
    {
        $roleData = [
            ['name' => 'admin', 'description' => 'Full system access'],
            ['name' => 'manager', 'description' => 'Restaurant operations manager'],
            ['name' => 'staff', 'description' => 'Service staff member'],
            ['name' => 'chef', 'description' => 'Kitchen preparation staff'],
            ['name' => 'customer', 'description' => 'Restaurant customer'],
            ['name' => 'cashier', 'description' => 'Handles payments and invoices'],
            ['name' => 'host', 'description' => 'Manages reservations and seating'],
            ['name' => 'bartender', 'description' => 'Beverage station staff'],
            ['name' => 'inventory', 'description' => 'Stock and supplier tracking'],
            ['name' => 'accountant', 'description' => 'Financial reporting'],
            ['name' => 'supervisor', 'description' => 'Shift supervision'],
        ];

        $roles = [];

        foreach ($roleData as $role) {
            $roles[$role['name']] = Role::updateOrCreate(['name' => $role['name']], $role);
        }

        return $roles;
    }

    private function seedPermissions()
    {
        $permissions = [
            ['name' => 'dashboard.view', 'display_name' => 'View Dashboard', 'description' => 'Access dashboard metrics'],
            ['name' => 'orders.view', 'display_name' => 'View Orders', 'description' => 'View order list and details'],
            ['name' => 'orders.create', 'display_name' => 'Create Orders', 'description' => 'Create new customer orders'],
            ['name' => 'orders.update', 'display_name' => 'Update Orders', 'description' => 'Update order status and items'],
            ['name' => 'payments.manage', 'display_name' => 'Manage Payments', 'description' => 'Create and reconcile payments'],
            ['name' => 'foods.view', 'display_name' => 'View Foods', 'description' => 'View menu items'],
            ['name' => 'foods.manage', 'display_name' => 'Manage Foods', 'description' => 'Create and update menu items'],
            ['name' => 'tables.manage', 'display_name' => 'Manage Tables', 'description' => 'Seat guests and update table states'],
            ['name' => 'inventory.manage', 'display_name' => 'Manage Inventory', 'description' => 'Manage ingredients and stock logs'],
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'description' => 'Access sales and KPI reports'],
            ['name' => 'employees.manage', 'display_name' => 'Manage Employees', 'description' => 'Maintain employee profiles'],
            ['name' => 'coupons.manage', 'display_name' => 'Manage Coupons', 'description' => 'Maintain discount campaigns'],
            ['name' => 'kitchen.manage', 'display_name' => 'Manage Kitchen', 'description' => 'Manage kitchen workflow'],
        ];

        return collect($permissions)->map(
            fn ($permission) => Permission::updateOrCreate(['name' => $permission['name']], $permission)
        );
    }

    private function seedDepartments()
    {
        $departments = [
            ['name' => 'Kitchen', 'description' => 'Kitchen and food preparation'],
            ['name' => 'Service', 'description' => 'Customer table service'],
            ['name' => 'Bar', 'description' => 'Beverage preparation'],
            ['name' => 'Front Desk', 'description' => 'Guest reception and reservations'],
            ['name' => 'Cashier', 'description' => 'Checkout and receipts'],
            ['name' => 'Inventory', 'description' => 'Stock management'],
            ['name' => 'Accounting', 'description' => 'Finance and reporting'],
            ['name' => 'Marketing', 'description' => 'Promotions and loyalty programs'],
            ['name' => 'Cleaning', 'description' => 'Dining room and kitchen cleaning'],
            ['name' => 'Management', 'description' => 'Restaurant leadership'],
        ];

        return collect($departments)->mapWithKeys(function ($department) {
            $model = Department::updateOrCreate(['name' => $department['name']], $department);

            return [$department['name'] => $model];
        });
    }

    private function seedEmployees($departments)
    {
        $employees = [
            ['email' => 'chef@restaurant.com', 'first_name' => 'Dung', 'last_name' => 'Pham', 'employee_id_number' => 'EMP001', 'department' => 'Kitchen', 'position' => 'Head Chef', 'salary' => 16000000],
            ['email' => 'staff@restaurant.com', 'first_name' => 'Chi', 'last_name' => 'Le', 'employee_id_number' => 'EMP002', 'department' => 'Service', 'position' => 'Senior Waiter', 'salary' => 9000000],
            ['email' => 'admin@restaurant.com', 'first_name' => 'An', 'last_name' => 'Nguyen', 'employee_id_number' => 'EMP003', 'department' => 'Management', 'position' => 'General Manager', 'salary' => 18000000],
            ['email' => 'manager@restaurant.com', 'first_name' => 'Binh', 'last_name' => 'Tran', 'employee_id_number' => 'EMP004', 'department' => 'Management', 'position' => 'Operations Manager', 'salary' => 15000000],
            ['email' => 'cashier@restaurant.com', 'first_name' => 'Em', 'last_name' => 'Hoang', 'employee_id_number' => 'EMP005', 'department' => 'Cashier', 'position' => 'Cashier', 'salary' => 8500000],
            ['email' => 'host@restaurant.com', 'first_name' => 'Giang', 'last_name' => 'Vo', 'employee_id_number' => 'EMP006', 'department' => 'Front Desk', 'position' => 'Host', 'salary' => 8200000],
            ['email' => 'bartender@restaurant.com', 'first_name' => 'Huy', 'last_name' => 'Dang', 'employee_id_number' => 'EMP007', 'department' => 'Bar', 'position' => 'Bartender', 'salary' => 9500000],
            ['email' => 'inventory@restaurant.com', 'first_name' => 'Khanh', 'last_name' => 'Do', 'employee_id_number' => 'EMP008', 'department' => 'Inventory', 'position' => 'Inventory Clerk', 'salary' => 8800000],
            ['email' => 'accountant@restaurant.com', 'first_name' => 'Lan', 'last_name' => 'Bui', 'employee_id_number' => 'EMP009', 'department' => 'Accounting', 'position' => 'Accountant', 'salary' => 12000000],
            ['email' => 'supervisor@restaurant.com', 'first_name' => 'Minh', 'last_name' => 'Ngo', 'employee_id_number' => 'EMP010', 'department' => 'Service', 'position' => 'Shift Supervisor', 'salary' => 11000000],
        ];

        return collect($employees)->map(function ($employee, $index) use ($departments) {
            $user = User::where('email', $employee['email'])->firstOrFail();

            return Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $employee['first_name'],
                    'last_name' => $employee['last_name'],
                    'employee_id_number' => $employee['employee_id_number'],
                    'department_id' => $departments[$employee['department']]->id,
                    'position' => $employee['position'],
                    'hire_date' => now()->subMonths(14 - $index)->toDateString(),
                    'salary' => $employee['salary'],
                    'phone' => '09123456' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'address' => (100 + $index) . ' Nguyen Trai, District ' . (($index % 10) + 1),
                    'status' => 'active',
                ]
            );
        });
    }

    private function assignDepartmentManagers($departments, $employees): void
    {
        $managers = $employees->values();

        $departments->values()->each(function (Department $department, int $index) use ($managers) {
            $department->update(['manager_id' => $managers[$index % $managers->count()]->id]);
        });
    }

    private function seedCategories()
    {
        $categories = [
            ['name' => 'Appetizers', 'description' => 'Small plates and starters', 'display_order' => 1],
            ['name' => 'Salads', 'description' => 'Fresh vegetable and protein salads', 'display_order' => 2],
            ['name' => 'Soups', 'description' => 'Warm soup selections', 'display_order' => 3],
            ['name' => 'Main Courses', 'description' => 'Signature main dishes', 'display_order' => 4],
            ['name' => 'Noodles', 'description' => 'Noodle and pasta dishes', 'display_order' => 5],
            ['name' => 'Rice Dishes', 'description' => 'Rice bowls and fried rice', 'display_order' => 6],
            ['name' => 'Seafood', 'description' => 'Fresh seafood dishes', 'display_order' => 7],
            ['name' => 'Vegetarian', 'description' => 'Plant-forward dishes', 'display_order' => 8],
            ['name' => 'Desserts', 'description' => 'Sweet desserts', 'display_order' => 9],
            ['name' => 'Beverages', 'description' => 'Tea, coffee, juice and cocktails', 'display_order' => 10],
        ];

        return collect($categories)->mapWithKeys(function ($category) {
            $model = Category::updateOrCreate(['name' => $category['name']], $category + ['is_active' => true]);

            return [$category['name'] => $model];
        });
    }

    private function seedFoods($categories)
    {
        $foods = [
            ['name' => 'Crispy Spring Rolls', 'category' => 'Appetizers', 'description' => 'Golden fried rolls with herbs', 'price' => 59000, 'preparation_time' => 12, 'spicy_level' => 0, 'calories' => 320, 'is_popular' => true],
            ['name' => 'Grilled Beef Skewers', 'category' => 'Appetizers', 'description' => 'Lemongrass beef skewers', 'price' => 79000, 'preparation_time' => 15, 'spicy_level' => 1, 'calories' => 410, 'is_popular' => true],
            ['name' => 'Lotus Stem Salad', 'category' => 'Salads', 'description' => 'Lotus stem, shrimp and herbs', 'price' => 89000, 'preparation_time' => 10, 'spicy_level' => 1, 'calories' => 280, 'is_popular' => false],
            ['name' => 'Chicken Caesar Salad', 'category' => 'Salads', 'description' => 'Romaine, grilled chicken and croutons', 'price' => 99000, 'preparation_time' => 10, 'spicy_level' => 0, 'calories' => 390, 'is_popular' => true],
            ['name' => 'Pumpkin Soup', 'category' => 'Soups', 'description' => 'Creamy pumpkin soup', 'price' => 65000, 'preparation_time' => 8, 'spicy_level' => 0, 'calories' => 240, 'is_popular' => false],
            ['name' => 'Seafood Tom Yum', 'category' => 'Soups', 'description' => 'Hot and sour seafood soup', 'price' => 119000, 'preparation_time' => 16, 'spicy_level' => 3, 'calories' => 360, 'is_popular' => true],
            ['name' => 'Grilled Chicken', 'category' => 'Main Courses', 'description' => 'Grilled chicken with vegetables', 'price' => 129000, 'preparation_time' => 22, 'spicy_level' => 1, 'calories' => 520, 'is_popular' => true],
            ['name' => 'Beef Steak Pepper Sauce', 'category' => 'Main Courses', 'description' => 'Beef steak with black pepper sauce', 'price' => 229000, 'preparation_time' => 25, 'spicy_level' => 1, 'calories' => 680, 'is_popular' => true],
            ['name' => 'Seafood Spaghetti', 'category' => 'Noodles', 'description' => 'Spaghetti with shrimp and squid', 'price' => 149000, 'preparation_time' => 18, 'spicy_level' => 1, 'calories' => 610, 'is_popular' => true],
            ['name' => 'Beef Pho', 'category' => 'Noodles', 'description' => 'Vietnamese beef noodle soup', 'price' => 89000, 'preparation_time' => 12, 'spicy_level' => 0, 'calories' => 540, 'is_popular' => true],
            ['name' => 'Garlic Fried Rice', 'category' => 'Rice Dishes', 'description' => 'Fried rice with garlic and egg', 'price' => 69000, 'preparation_time' => 10, 'spicy_level' => 0, 'calories' => 470, 'is_popular' => false],
            ['name' => 'Clay Pot Rice', 'category' => 'Rice Dishes', 'description' => 'Crispy rice with chicken and mushrooms', 'price' => 109000, 'preparation_time' => 20, 'spicy_level' => 0, 'calories' => 650, 'is_popular' => true],
            ['name' => 'Garlic Butter Shrimp', 'category' => 'Seafood', 'description' => 'Shrimp sauteed with garlic butter', 'price' => 179000, 'preparation_time' => 15, 'spicy_level' => 1, 'calories' => 430, 'is_popular' => true],
            ['name' => 'Steamed Sea Bass', 'category' => 'Seafood', 'description' => 'Sea bass steamed with ginger', 'price' => 249000, 'preparation_time' => 28, 'spicy_level' => 0, 'calories' => 500, 'is_popular' => false],
            ['name' => 'Tofu Mushroom Claypot', 'category' => 'Vegetarian', 'description' => 'Tofu and mushrooms in soy sauce', 'price' => 99000, 'preparation_time' => 16, 'spicy_level' => 0, 'calories' => 360, 'is_popular' => false],
            ['name' => 'Vegetable Curry', 'category' => 'Vegetarian', 'description' => 'Coconut vegetable curry', 'price' => 109000, 'preparation_time' => 18, 'spicy_level' => 2, 'calories' => 480, 'is_popular' => true],
            ['name' => 'Chocolate Cake', 'category' => 'Desserts', 'description' => 'Rich chocolate layer cake', 'price' => 59000, 'preparation_time' => 6, 'spicy_level' => 0, 'calories' => 420, 'is_popular' => true],
            ['name' => 'Mango Panna Cotta', 'category' => 'Desserts', 'description' => 'Creamy panna cotta with mango', 'price' => 69000, 'preparation_time' => 6, 'spicy_level' => 0, 'calories' => 310, 'is_popular' => false],
            ['name' => 'Vietnamese Iced Coffee', 'category' => 'Beverages', 'description' => 'Strong coffee with condensed milk', 'price' => 45000, 'preparation_time' => 5, 'spicy_level' => 0, 'calories' => 180, 'is_popular' => true],
            ['name' => 'Passion Fruit Soda', 'category' => 'Beverages', 'description' => 'Fresh passion fruit and soda', 'price' => 49000, 'preparation_time' => 5, 'spicy_level' => 0, 'calories' => 140, 'is_popular' => true],
        ];

        return collect($foods)->map(function ($food) use ($categories) {
            $aiProfile = $this->foodAiSeedProfile($food);

            return Food::updateOrCreate(
                ['name' => $food['name'], 'category_id' => $categories[$food['category']]->id],
                [
                    'description' => $food['description'],
                    'price' => $food['price'],
                    'preparation_time' => $food['preparation_time'],
                    'spicy_level' => $food['spicy_level'],
                    'calories' => $food['calories'],
                    'allergens' => $aiProfile['allergens'],
                    'ingredients' => $aiProfile['ingredients'],
                    'nutrition' => $aiProfile['nutrition'],
                    'diet_tags' => $aiProfile['diet_tags'],
                    'taste_profile' => $aiProfile['taste_profile'],
                    'best_for' => $aiProfile['best_for'],
                    'is_available' => true,
                    'is_popular' => $food['is_popular'],
                ]
            );
        });
    }

    private function foodAiSeedProfile(array $food): array
    {
        $name = $food['name'];
        $category = $food['category'];
        $description = strtolower($food['description']);
        $isDessert = $category === 'Desserts';
        $isDrink = $category === 'Beverages';
        $isSeafood = $category === 'Seafood' || str_contains($description, 'seafood') || str_contains($description, 'shrimp') || str_contains($description, 'squid') || str_contains($description, 'bass');
        $isVegetarian = $category === 'Vegetarian' || str_contains($description, 'tofu') || str_contains($description, 'vegetable');
        $isLight = in_array($category, ['Salads', 'Soups', 'Vegetarian', 'Beverages'], true) && !$isDessert;
        $isSpicy = $food['spicy_level'] >= 2;

        $ingredients = match (true) {
            str_contains($name, 'Chicken') => ['chicken', 'vegetables', 'herbs'],
            str_contains($name, 'Beef') || str_contains($name, 'Steak') || str_contains($name, 'Pho') => ['beef', 'herbs', 'spices'],
            $isSeafood => ['seafood', 'garlic', 'herbs'],
            str_contains($name, 'Cake') => ['chocolate', 'flour', 'cream', 'sugar'],
            str_contains($name, 'Panna') => ['cream', 'mango', 'sugar'],
            str_contains($name, 'Coffee') => ['coffee', 'condensed milk', 'sugar'],
            str_contains($name, 'Soda') => ['passion fruit', 'soda', 'sugar'],
            $isVegetarian => ['vegetables', 'tofu', 'mushrooms'],
            default => ['herbs', 'vegetables', 'house seasoning'],
        };

        $sugar = match (true) {
            $isDessert => str_contains($name, 'Cake') ? 34 : 24,
            $isDrink => str_contains($name, 'Coffee') ? 18 : 22,
            str_contains($name, 'Pumpkin') => 7,
            in_array($category, ['Salads', 'Soups', 'Seafood', 'Vegetarian'], true) => 3,
            default => 5,
        };

        $allergens = array_values(array_filter([
            in_array($category, ['Noodles', 'Appetizers', 'Desserts'], true) ? 'gluten' : null,
            $isSeafood ? 'seafood' : null,
            ($isDessert || str_contains($name, 'Coffee') || str_contains($name, 'Caesar')) ? 'dairy' : null,
            str_contains($name, 'Fried Rice') ? 'egg' : null,
        ]));

        return [
            'ingredients' => $ingredients,
            'nutrition' => [
                'calories' => $food['calories'],
                'sugar_g' => $sugar,
                'protein_level' => $isVegetarian || $isDessert || $isDrink ? 'medium' : 'high',
                'carb_level' => in_array($category, ['Noodles', 'Rice Dishes', 'Desserts'], true) ? 'high' : 'medium',
            ],
            'allergens' => $allergens,
            'diet_tags' => array_values(array_filter([
                $isVegetarian ? 'vegetarian' : null,
                $isLight ? 'light' : null,
                $sugar <= 5 ? 'low_sugar' : null,
                $food['spicy_level'] === 0 ? 'not_spicy' : null,
                !$isSpicy && !$isDrink ? 'kid_friendly' : null,
                $isSeafood ? 'seafood' : null,
            ])),
            'taste_profile' => array_values(array_filter([
                $isSpicy ? 'spicy' : null,
                $isDessert || $isDrink ? 'sweet' : null,
                str_contains($description, 'sour') ? 'sour' : null,
                str_contains($description, 'grilled') ? 'smoky' : null,
                $isLight ? 'fresh' : null,
                str_contains($description, 'creamy') || str_contains($description, 'butter') ? 'creamy' : null,
            ])),
            'best_for' => array_values(array_filter([
                $food['preparation_time'] <= 10 ? 'quick_order' : null,
                $isLight ? 'light_meal' : null,
                $food['price'] <= 80000 ? 'budget' : null,
                $food['is_popular'] ? 'popular_choice' : null,
                in_array($category, ['Main Courses', 'Seafood', 'Rice Dishes'], true) ? 'main_meal' : null,
                $isDessert ? 'dessert' : null,
                $isDrink ? 'drink' : null,
            ])),
        ];
    }

    private function seedIngredients()
    {
        $ingredients = [
            ['name' => 'Chicken Breast', 'category' => 'Meat', 'unit' => 'kg', 'current_quantity' => 45, 'min_quantity' => 10, 'max_quantity' => 80, 'unit_cost' => 78000],
            ['name' => 'Beef Tenderloin', 'category' => 'Meat', 'unit' => 'kg', 'current_quantity' => 28, 'min_quantity' => 8, 'max_quantity' => 50, 'unit_cost' => 260000],
            ['name' => 'Shrimp', 'category' => 'Seafood', 'unit' => 'kg', 'current_quantity' => 32, 'min_quantity' => 8, 'max_quantity' => 60, 'unit_cost' => 180000],
            ['name' => 'Squid', 'category' => 'Seafood', 'unit' => 'kg', 'current_quantity' => 20, 'min_quantity' => 5, 'max_quantity' => 40, 'unit_cost' => 150000],
            ['name' => 'Sea Bass', 'category' => 'Seafood', 'unit' => 'kg', 'current_quantity' => 18, 'min_quantity' => 5, 'max_quantity' => 35, 'unit_cost' => 220000],
            ['name' => 'Tofu', 'category' => 'Vegetarian', 'unit' => 'kg', 'current_quantity' => 24, 'min_quantity' => 6, 'max_quantity' => 45, 'unit_cost' => 28000],
            ['name' => 'Mushroom', 'category' => 'Vegetable', 'unit' => 'kg', 'current_quantity' => 22, 'min_quantity' => 5, 'max_quantity' => 40, 'unit_cost' => 65000],
            ['name' => 'Romaine Lettuce', 'category' => 'Vegetable', 'unit' => 'kg', 'current_quantity' => 16, 'min_quantity' => 4, 'max_quantity' => 30, 'unit_cost' => 42000],
            ['name' => 'Lotus Stem', 'category' => 'Vegetable', 'unit' => 'kg', 'current_quantity' => 19, 'min_quantity' => 5, 'max_quantity' => 35, 'unit_cost' => 50000],
            ['name' => 'Pumpkin', 'category' => 'Vegetable', 'unit' => 'kg', 'current_quantity' => 36, 'min_quantity' => 8, 'max_quantity' => 70, 'unit_cost' => 24000],
            ['name' => 'Rice', 'category' => 'Grain', 'unit' => 'kg', 'current_quantity' => 120, 'min_quantity' => 30, 'max_quantity' => 200, 'unit_cost' => 18000],
            ['name' => 'Spaghetti', 'category' => 'Grain', 'unit' => 'kg', 'current_quantity' => 42, 'min_quantity' => 10, 'max_quantity' => 80, 'unit_cost' => 55000],
            ['name' => 'Rice Noodles', 'category' => 'Grain', 'unit' => 'kg', 'current_quantity' => 55, 'min_quantity' => 12, 'max_quantity' => 90, 'unit_cost' => 38000],
            ['name' => 'Egg', 'category' => 'Dairy', 'unit' => 'piece', 'current_quantity' => 240, 'min_quantity' => 60, 'max_quantity' => 400, 'unit_cost' => 3500],
            ['name' => 'Butter', 'category' => 'Dairy', 'unit' => 'kg', 'current_quantity' => 12, 'min_quantity' => 3, 'max_quantity' => 25, 'unit_cost' => 120000],
            ['name' => 'Cream', 'category' => 'Dairy', 'unit' => 'l', 'current_quantity' => 18, 'min_quantity' => 4, 'max_quantity' => 35, 'unit_cost' => 85000],
            ['name' => 'Chocolate', 'category' => 'Bakery', 'unit' => 'kg', 'current_quantity' => 15, 'min_quantity' => 4, 'max_quantity' => 30, 'unit_cost' => 145000],
            ['name' => 'Mango', 'category' => 'Fruit', 'unit' => 'kg', 'current_quantity' => 26, 'min_quantity' => 6, 'max_quantity' => 45, 'unit_cost' => 48000],
            ['name' => 'Passion Fruit', 'category' => 'Fruit', 'unit' => 'kg', 'current_quantity' => 21, 'min_quantity' => 5, 'max_quantity' => 40, 'unit_cost' => 52000],
            ['name' => 'Coffee Beans', 'category' => 'Beverage', 'unit' => 'kg', 'current_quantity' => 14, 'min_quantity' => 4, 'max_quantity' => 30, 'unit_cost' => 190000],
        ];

        return collect($ingredients)->map(function ($ingredient) {
            return Ingredient::updateOrCreate(
                ['name' => $ingredient['name']],
                $ingredient + ['description' => $ingredient['name'] . ' for restaurant menu', 'is_active' => true]
            );
        });
    }

    private function seedRecipes($foods)
    {
        return $foods->take(10)->values()->map(function (Food $food) {
            return Recipe::updateOrCreate(
                ['food_id' => $food->id],
                [
                    'name' => $food->name . ' Recipe',
                    'yield_quantity' => 1,
                    'yield_unit' => 'serving',
                    'preparation_instructions' => 'Prepare ingredients, cook to order, plate and garnish before serving.',
                ]
            );
        });
    }

    private function seedRecipeItems($recipes, $ingredients): void
    {
        $ingredients = $ingredients->values();

        $recipes->values()->each(function (Recipe $recipe, int $recipeIndex) use ($ingredients) {
            for ($i = 0; $i < 3; $i++) {
                $ingredient = $ingredients[($recipeIndex + $i) % $ingredients->count()];

                RecipeItem::updateOrCreate(
                    ['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id],
                    ['quantity' => 0.25 + ($i * 0.1), 'unit' => $ingredient->unit]
                );
            }
        });
    }

    private function seedTables()
    {
        return collect(range(1, 20))->map(function (int $number) {
            $sections = ['Main Hall', 'Window', 'Garden', 'Private Room'];
            $status = $number % 7 === 0 ? 'reserved' : ($number % 5 === 0 ? 'occupied' : 'empty');

            return Table::updateOrCreate(
                ['table_number' => $number],
                [
                    'capacity' => [2, 4, 6, 8][$number % 4],
                    'section' => $sections[$number % count($sections)],
                    'status' => $status,
                    'current_customer_count' => $status === 'occupied' ? min(4, [2, 4, 6, 8][$number % 4]) : 0,
                    'occupied_since' => $status === 'occupied' ? now()->subMinutes(30 + $number) : null,
                    'reserved_until' => $status === 'reserved' ? now()->addHours(2) : null,
                    'is_active' => true,
                ]
            );
        });
    }

    private function seedCoupons($employees)
    {
        $creator = $employees->first();
        $coupons = [
            ['code' => 'WELCOME10', 'name' => 'Welcome 10%', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_value' => 100000],
            ['code' => 'LUNCH20K', 'name' => 'Lunch 20K', 'discount_type' => 'fixed_amount', 'discount_value' => 20000, 'min_order_value' => 150000],
            ['code' => 'FAMILY15', 'name' => 'Family 15%', 'discount_type' => 'percent', 'discount_value' => 15, 'min_order_value' => 300000],
            ['code' => 'DINNER30K', 'name' => 'Dinner 30K', 'discount_type' => 'fixed_amount', 'discount_value' => 30000, 'min_order_value' => 250000],
            ['code' => 'VIP25', 'name' => 'VIP 25%', 'discount_type' => 'percent', 'discount_value' => 25, 'min_order_value' => 500000],
            ['code' => 'COFFEE10K', 'name' => 'Coffee 10K', 'discount_type' => 'fixed_amount', 'discount_value' => 10000, 'min_order_value' => 50000],
            ['code' => 'SEAFOOD12', 'name' => 'Seafood 12%', 'discount_type' => 'percent', 'discount_value' => 12, 'min_order_value' => 350000],
            ['code' => 'BIRTHDAY50K', 'name' => 'Birthday 50K', 'discount_type' => 'fixed_amount', 'discount_value' => 50000, 'min_order_value' => 400000],
            ['code' => 'WEEKDAY8', 'name' => 'Weekday 8%', 'discount_type' => 'percent', 'discount_value' => 8, 'min_order_value' => 120000],
            ['code' => 'TAKEAWAY15K', 'name' => 'Takeaway 15K', 'discount_type' => 'fixed_amount', 'discount_value' => 15000, 'min_order_value' => 100000],
        ];

        return collect($coupons)->map(function ($coupon, $index) use ($creator) {
            return Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon + [
                    'description' => 'Sample promotion campaign',
                    'max_uses_per_customer' => 1,
                    'total_uses_limit' => 100 + ($index * 10),
                    'current_uses' => $index,
                    'start_date' => now()->subDays(7)->toDateString(),
                    'end_date' => now()->addDays(60)->toDateString(),
                    'is_active' => true,
                    'created_by_id' => $creator->id,
                ]
            );
        });
    }

    private function seedTableReservations($tables, $foods): void
    {
        $names = ['Nguyen Van A', 'Tran Thi B', 'Le Minh C', 'Pham Thu D', 'Hoang Gia E', 'Do Anh F', 'Bui Ngoc G', 'Vo Bao H', 'Dang Khoa I', 'Ngo Ha K'];

        foreach ($names as $index => $name) {
            $table = $tables[$index % $tables->count()];
            $food = $foods[$index % $foods->count()];

            TableReservation::updateOrCreate(
                ['customer_phone' => '09876543' . str_pad((string) $index, 2, '0', STR_PAD_LEFT)],
                [
                    'table_id' => $table->id,
                    'customer_name' => $name,
                    'customer_email' => 'guest' . ($index + 1) . '@example.com',
                    'reservation_time' => now()->addDays($index + 1)->setTime(18 + ($index % 3), 0),
                    'number_of_guests' => min($table->capacity, 2 + ($index % 5)),
                    'special_requests' => $index % 2 === 0 ? 'Window seat if available' : null,
                    'pre_order_items' => [['food_id' => $food->id, 'quantity' => 1]],
                    'status' => ['pending', 'confirmed', 'completed'][$index % 3],
                ]
            );
        }
    }

    private function seedOrders($tables, $foods, $employees, $coupons)
    {
        $statuses = ['pending', 'confirmed', 'in_progress', 'ready', 'served', 'paid', 'cancelled', 'paid', 'served', 'confirmed'];

        return collect(range(1, 10))->map(function (int $number) use ($tables, $foods, $employees, $coupons, $statuses) {
            $selectedFoods = $foods->values()->slice(($number - 1) * 2, 2)->values();
            $subtotal = 0;

            $order = Order::updateOrCreate(
                ['order_number' => 'ORD-' . now()->format('Ymd') . '-' . str_pad((string) $number, 4, '0', STR_PAD_LEFT)],
                [
                    'table_id' => $tables[($number - 1) % $tables->count()]->id,
                    'status' => $statuses[$number - 1],
                    'created_by_id' => $employees[($number - 1) % $employees->count()]->id,
                    'source' => $number % 3 === 0 ? 'takeaway' : 'dine_in',
                    'customer_notes' => $number % 2 === 0 ? 'Less ice for drinks' : null,
                    'special_requests' => $number % 4 === 0 ? 'Serve main dishes together' : null,
                    'estimated_completion_time' => now()->addMinutes(20 + $number),
                    'actual_completion_time' => in_array($statuses[$number - 1], ['served', 'paid'], true) ? now()->subMinutes(5) : null,
                    'paid_at' => $statuses[$number - 1] === 'paid' ? now()->subMinutes(2) : null,
                    'coupon_id' => $number % 2 === 0 ? $coupons[($number - 1) % $coupons->count()]->id : null,
                ]
            );

            $selectedFoods->each(function (Food $food, int $index) use ($order, $number, &$subtotal) {
                $quantity = 1 + (($number + $index) % 3);
                $total = $food->price * $quantity;
                $subtotal += $total;

                OrderItem::updateOrCreate(
                    ['order_id' => $order->id, 'food_id' => $food->id],
                    [
                        'quantity' => $quantity,
                        'unit_price' => $food->price,
                        'total_price' => $total,
                        'special_notes' => $index === 0 ? 'Sample item note' : null,
                        'status' => ['pending', 'preparing', 'ready', 'served'][$number % 4],
                    ]
                );
            });

            $tax = round($subtotal * 0.08, 2);
            $serviceCharge = round($subtotal * 0.05, 2);
            $discount = $order->coupon_id ? min(50000, round($subtotal * 0.1, 2)) : 0;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'service_charge' => $serviceCharge,
                'discount_amount' => $discount,
                'total_price' => $subtotal + $tax + $serviceCharge - $discount,
            ]);

            return $order->refresh();
        });
    }

    private function seedPayments($orders, $employees)
    {
        $methods = ['cash', 'qr_code', 'card', 'digital_wallet'];
        $gateways = ['manual', 'vietqr', 'vnpay', 'momo'];

        return $orders->values()->map(function (Order $order, int $index) use ($employees, $methods, $gateways) {
            return Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'amount' => $order->total_price,
                    'payment_method' => $methods[$index % count($methods)],
                    'payment_gateway' => $gateways[$index % count($gateways)],
                    'transaction_id' => 'TXN' . now()->format('Ymd') . str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT),
                    'status' => $order->status === 'cancelled' ? 'failed' : ($index % 3 === 0 ? 'pending' : 'completed'),
                    'paid_at' => $index % 3 === 0 ? null : now()->subMinutes(15 - $index),
                    'receipt_number' => 'RCPT-' . now()->format('Ymd') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'created_by_id' => $employees[$index % $employees->count()]->id,
                    'notes' => 'Sample payment record',
                ]
            );
        });
    }

    private function seedInvoices($payments): void
    {
        $payments->values()->each(function (Payment $payment, int $index) {
            $tax = round($payment->amount * 0.08 / 1.13, 2);
            $subtotal = $payment->amount - $tax;

            Invoice::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => 0,
                    'total' => $payment->amount,
                    'issued_at' => now()->subDays($index),
                    'due_date' => now()->addDays(7 - $index),
                    'status' => $payment->status === 'completed' ? 'paid' : 'issued',
                ]
            );
        });
    }

    private function seedInventoryLogs($ingredients, $employees): void
    {
        $actions = ['stock_in', 'stock_out', 'adjustment', 'waste'];

        $ingredients->take(10)->values()->each(function (Ingredient $ingredient, int $index) use ($employees, $actions) {
            InventoryLog::updateOrCreate(
                ['ingredient_id' => $ingredient->id, 'reference_type' => 'seed', 'reference_id' => $index + 1],
                [
                    'action_type' => $actions[$index % count($actions)],
                    'quantity_change' => ($index % 2 === 0 ? 1 : -1) * (2 + $index),
                    'notes' => 'Sample inventory movement',
                    'created_by' => $employees[$index % $employees->count()]->user_id,
                ]
            );
        });
    }

    private function seedAuditLogs(): void
    {
        $users = User::take(10)->get()->values();
        $actions = ['created', 'updated', 'viewed', 'deleted', 'exported'];

        $users->each(function (User $user, int $index) use ($actions) {
            AuditLog::updateOrCreate(
                ['user_id' => $user->id, 'action' => $actions[$index % count($actions)], 'model_id' => $index + 1],
                [
                    'model_type' => Order::class,
                    'old_values' => ['status' => 'pending'],
                    'new_values' => ['status' => 'confirmed'],
                    'ip_address' => '127.0.0.' . ($index + 1),
                    'user_agent' => 'Seeder Bot',
                ]
            );
        });
    }
}
