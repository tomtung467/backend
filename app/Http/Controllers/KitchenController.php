<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Kitchen\KitchenQueueService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class KitchenController extends Controller
{
    protected $kitchenQueueService;

    public function __construct(KitchenQueueService $kitchenQueueService)
    {
        $this->kitchenQueueService = $kitchenQueueService;
    }

    public function getQueue()
    {
        $queue = $this->kitchenQueue();

        return response()->json($queue);
    }

    public function streamQueue(Request $request): StreamedResponse
    {
        try {
            JWTAuth::setToken($request->query('token'))->authenticate();
        } catch (\Throwable $e) {
            abort(401, 'Unauthorized');
        }

        return response()->stream(function () {
            $lastSignature = null;
            $startedAt = time();

            while (time() - $startedAt < 55) {
                if (connection_aborted()) {
                    break;
                }

                $queue = $this->kitchenQueue();
                $signature = $queue
                    ->map(function ($order) {
                        $itemsSignature = $order->orderItems
                            ->map(fn ($item) => "{$item->id}:{$item->status}:{$item->updated_at?->timestamp}")
                            ->implode(',');

                        return "{$order->id}:{$order->status}:{$order->updated_at?->timestamp}:{$itemsSignature}";
                    })
                    ->implode('|');

                if ($signature !== $lastSignature) {
                    echo "event: queue\n";
                    echo 'data: ' . $queue->toJson() . "\n\n";
                    $lastSignature = $signature;
                } else {
                    echo ": keepalive\n\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,in_progress,ready,served,paid,cancelled',
        ]);

        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);
        $this->syncOrderItemStatus($order, $validated['status']);

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
        $this->syncOrderItemStatus($order, 'served');
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

    private function kitchenQueue()
    {
        return Order::whereNotIn('status', ['served', 'paid', 'cancelled'])
            ->whereHas('orderItems', fn ($items) => $items->where('status', '!=', 'cancelled'))
            ->with(['orderItems' => fn ($items) => $items->where('status', '!=', 'cancelled')->with('food')])
            ->orderBy('created_at')
            ->get()
            ->map(function ($order) {
                $this->normalizeKitchenQueueStatus($order);
                return $order;
            });
    }

    private function syncOrderItemStatus(Order $order, string $orderStatus): void
    {
        $itemStatus = match ($orderStatus) {
            'in_progress' => 'preparing',
            'ready' => 'ready',
            'served' => 'served',
            'cancelled' => 'cancelled',
            default => null,
        };

        if (!$itemStatus) {
            return;
        }

        $order->items()
            ->where('status', '!=', 'cancelled')
            ->update(['status' => $itemStatus]);
    }

    private function normalizeKitchenQueueStatus(Order $order): void
    {
        if (in_array($order->status, ['paid', 'cancelled', 'served'], true)) {
            return;
        }

        if ($order->orderItems->contains(fn ($item) => $item->status === 'preparing') && $order->status !== 'in_progress') {
            $order->update(['status' => 'in_progress']);
            $order->status = 'in_progress';
        }
    }
}
