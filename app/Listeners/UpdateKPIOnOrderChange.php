<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\KPISnapshot;
use Illuminate\Support\Facades\Log;

class UpdateKPIOnOrderChange
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event)
    {
        try {
            $order = $event->order;

            // Update KPI when order is completed
            if ($event->order->status === 'served' || $event->order->status === 'completed') {
                $chef = $order->employee;

                if ($chef) {
                    KPISnapshot::updateOrCreate(
                        [
                            'employee_id' => $chef->id,
                            'date' => now()->toDateString(),
                        ],
                        [
                            'orders_completed' => KPISnapshot::where('employee_id', $chef->id)
                                ->where('date', now()->toDateString())
                                ->value('orders_completed') + 1,
                        ]
                    );
                }
            }

            Log::info("KPI updated for order {$order->id}");
        } catch (\Exception $e) {
            Log::error("Error updating KPI: " . $e->getMessage());
        }
    }
}
