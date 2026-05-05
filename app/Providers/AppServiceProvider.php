<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Services
use App\Services\Inventory\InventoryService;
use App\Services\Employee\EmployeeService;
use App\Services\Employee\IEmployeeService;
use App\Services\Billing\BillingService;
use App\Services\Kitchen\KitchenQueueService;
use App\Services\Kitchen\IKitchenQueueService;
use App\Services\Menu\MenuService;
use App\Services\Menu\IMenuService;
use App\Services\Orders\OrderService;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\IAnalyticsService;
use App\Services\Tables\TableService;
use App\Services\Tables\ITableService;
use App\Services\User\UserService;
use App\Services\User\IUserService;
use App\Services\Category\CategoryService;
use App\Services\Category\ICategoryService;

// Repositories
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Inventory\IInventoryRepository;
use App\Repositories\Employee\EmployeeRepository;
use App\Repositories\Employee\IEmployeeRepository;
use App\Repositories\Billing\BillingRepository;
use App\Repositories\Billing\IBillingRepository;
use App\Repositories\Menu\MenuRepository;
use App\Repositories\Menu\IMenuRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Orders\IOrderRepository;
use App\Repositories\Tables\TableRepository;
use App\Repositories\Tables\ITableRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\User\IUserRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Category\ICategoryRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repository Bindings - Interfaces to Implementations
        $this->app->bind(IInventoryRepository::class, InventoryRepository::class);
        $this->app->bind(IEmployeeRepository::class, EmployeeRepository::class);
        $this->app->bind(IBillingRepository::class, BillingRepository::class);
        $this->app->bind(IMenuRepository::class, MenuRepository::class);
        $this->app->bind(IOrderRepository::class, OrderRepository::class);
        $this->app->bind(ITableRepository::class, TableRepository::class);
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(ICategoryRepository::class, CategoryRepository::class);

        // Register Services
        $this->app->bind(InventoryService::class, function ($app) {
            return new InventoryService($app->make(InventoryRepository::class));
        });

        $this->app->bind(IEmployeeService::class, function ($app) {
            return new EmployeeService($app->make(IEmployeeRepository::class));
        });

        $this->app->bind(BillingService::class, function ($app) {
            return new BillingService($app->make(BillingRepository::class));
        });

        $this->app->bind(IMenuService::class, function ($app) {
            return new MenuService($app->make(MenuRepository::class));
        });

        $this->app->bind(OrderService::class, function ($app) {
            return new OrderService($app->make(InventoryService::class));
        });

        $this->app->bind(IAnalyticsService::class, function ($app) {
            return new AnalyticsService();
        });

        $this->app->bind(ITableService::class, function ($app) {
            return new TableService();
        });

        $this->app->bind(IKitchenQueueService::class, function ($app) {
            return new KitchenQueueService();
        });

        $this->app->bind(IUserService::class, function ($app) {
            return new UserService($app->make(IUserRepository::class));
        });

        $this->app->bind(ICategoryService::class, function ($app) {
            return new CategoryService($app->make(ICategoryRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
