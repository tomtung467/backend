<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class LogUserLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            $user = $event->user;

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => ['user_id' => $user->id, 'email' => $user->email],
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            Log::info("User {$user->email} logged in from " . request()->ip());
        } catch (\Exception $e) {
            Log::error("Error logging user login: " . $e->getMessage());
        }
    }
}
