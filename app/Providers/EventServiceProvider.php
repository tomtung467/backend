<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\OrderStatusChanged;
use App\Listeners\LogOrderStatusChange;
use App\Listeners\UpdateKPIOnOrderChange;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderStatusChanged::class => [
            LogOrderStatusChange::class,
            UpdateKPIOnOrderChange::class,
        ],
        Login::class => [
            LogUserLogin::class,
        ],
        Logout::class => [
            LogUserLogout::class,
        ],
    ];

    /**
     * Enable the loading of event listeners from discovery.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
