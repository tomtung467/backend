<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;

class LogUserLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            $user = $event->user;

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'model_type' => 'User',
                'model_id' => $user->id,
                'old_values' => null,
                'new_values' => ['user_id' => $user->id],
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            Log::info("User {$user->email} logged out");
        } catch (\Exception $e) {
            Log::error("Error logging user logout: " . $e->getMessage());
        }
    }
}
