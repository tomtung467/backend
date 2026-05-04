<?php

return [
    /**
     * Repository Bindings
     * Map interfaces to implementations
     */
    'repositories' => [
        'App\Repositories\IBaseRepository' => 'App\Repositories\BaseRepository',
        'App\Repositories\Menu\IMenuRepository' => 'App\Repositories\Menu\MenuRepository',
        'App\Repositories\Orders\IOrderRepository' => 'App\Repositories\Orders\OrderRepository',
        'App\Repositories\Inventory\IInventoryRepository' => 'App\Repositories\Inventory\InventoryRepository',
        'App\Repositories\Billing\IBillingRepository' => 'App\Repositories\Billing\BillingRepository',
        'App\Repositories\Tables\ITableRepository' => 'App\Repositories\Tables\TableRepository',
        'App\Repositories\User\IUserRepository' => 'App\Repositories\User\UserRepository',
        'App\Repositories\Employee\IEmployeeRepository' => 'App\Repositories\Employee\EmployeeRepository',
        'App\Repositories\Category\ICategoryRepository' => 'App\Repositories\Category\CategoryRepository',
    ],

    /**
     * Service Bindings
     * Map interfaces to implementations
     */
    'services' => [
        'App\Services\Menu\IMenuService' => 'App\Services\Menu\MenuService',
        'App\Services\User\IUserService' => 'App\Services\User\UserService',
        'App\Services\Employee\IEmployeeService' => 'App\Services\Employee\EmployeeService',
        'App\Services\Category\ICategoryService' => 'App\Services\Category\CategoryService',
    ],

    /**
     * Policy Bindings
     */
    'policies' => [
        'App\Models\Order' => 'App\Policies\OrderPolicy',
        'App\Models\Employee' => 'App\Policies\EmployeePolicy',
        'App\Models\Table' => 'App\Policies\TablePolicy',
        'App\Models\Food' => 'App\Policies\MenuPolicy',
        'App\Models\Ingredient' => 'App\Policies\InventoryPolicy',
    ],
];
