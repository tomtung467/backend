<?php

namespace App\Services\Kitchen;

use App\Models\Order;

interface IKitchenQueueService
{
    public function getQueue();
    public function updateOrderStatus($orderId, $status);
    public function getReadyOrders();
}

class KitchenQueueService implements IKitchenQueueService
{
    public function getQueue()
    {
        return Order::where('status', '!=', 'served')
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->with('orderItems.food')
            ->orderBy('created_at')
            ->get();
    }

    public function updateOrderStatus($orderId, $status)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        // Broadcast status change
        broadcast(new \App\Events\OrderStatusChanged($order));

        return $order;
    }

    public function getReadyOrders()
    {
        return Order::where('status', 'ready')
            ->with('table')
            ->orderBy('updated_at')
            ->get();
    }

    public function completeOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => 'served', 'actual_completion_time' => now()]);

        return $order;
    }
}
