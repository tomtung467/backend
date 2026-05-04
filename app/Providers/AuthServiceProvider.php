<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Order;
use App\Models\Employee;
use App\Models\Table;
use App\Models\Food;
use App\Models\Ingredient;
use App\Policies\OrderPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\TablePolicy;
use App\Policies\MenuPolicy;
use App\Policies\InventoryPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
        Employee::class => EmployeePolicy::class,
        Table::class => TablePolicy::class,
        Food::class => MenuPolicy::class,
        Ingredient::class => InventoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates here if needed
    }
}
