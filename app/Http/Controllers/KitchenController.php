<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Kitchen\KitchenQueueService;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    protected $kitchenQueueService;

    public function __construct(KitchenQueueService $kitchenQueueService)
    {
        $this->kitchenQueueService = $kitchenQueueService;
    }

    public function getQueue()
    {
        $queue = Order::where('status', '!=', 'served')
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->with('orderItems.food')
            ->orderBy('created_at')
            ->get();

        return response()->json($queue);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,in_progress,ready,served,paid,cancelled',
        ]);

        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Broadcast to kitchen display system
        broadcast(new \App\Events\OrderStatusChanged($order, $oldStatus));

        return response()->json($order);
    }

    public function getOrderDetails($orderId)
    {
        $order = Order::with(['orderItems.food', 'table', 'employee'])->findOrFail($orderId);
        return response()->json($order);
    }

    public function getReadyOrders()
    {
        $readyOrders = Order::where('status', 'ready')
            ->with('table')
            ->orderBy('updated_at')
            ->get();

        return response()->json($readyOrders);
    }

    public function completeOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => 'served', 'actual_completion_time' => now()]);
        broadcast(new \App\Events\OrderStatusChanged($order, $oldStatus));

        return response()->json($order);
    }

    public function printOrder($orderId)
    {
        $order = Order::with('orderItems.food')->findOrFail($orderId);
        // Generate kitchen receipt
        return response()->json([
            'order' => $order,
            'print_status' => 'sent_to_printer',
        ]);
    }
}
