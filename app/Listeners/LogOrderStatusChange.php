<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogOrderStatusChange
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event)
    {
        try {
            // Log the status change
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'order_status_changed',
                'model_type' => 'Order',
                'model_id' => $event->order->id,
                'old_values' => ['status' => $event->oldStatus],
                'new_values' => ['status' => $event->order->status],
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            Log::info("Order {$event->order->id} status changed from {$event->oldStatus} to {$event->order->status}");
        } catch (\Exception $e) {
            Log::error("Error logging order status change: " . $e->getMessage());
        }
    }
}
