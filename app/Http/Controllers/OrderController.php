<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderService;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class OrderController extends Controller
{
    use ApiResponseTrait;

    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get all orders
     * GET /api/v1/orders
     */
    public function index(Request $request)
    {
        try {
            $query = Order::query();

            if (!$request->boolean('summary')) {
                $query->with('items.food', 'payment');
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('table_id')) {
                $query->where('table_id', $request->input('table_id'));
            }

            // Filter by date range if provided
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('created_at', [
                    $request->input('date_from'),
                    $request->input('date_to')
                ]);
            }

            if ($request->boolean('summary')) {
                $orders = $query->select(
                    'id',
                    'order_number',
                    'table_id',
                    'status',
                    'total_price',
                    'payment_requested_at',
                    'created_at'
                )
                    ->orderBy('created_at', 'desc')
                    ->limit((int) $request->query('limit', 15))
                    ->get();
            } else {
                $orders = $query->orderBy('created_at', 'desc')
                    ->paginate((int) $request->query('per_page', 15));
            }

            return $this->success(
                OrderResource::collection($orders),
                'Orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Create new order
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by_id'] = Employee::where('user_id', Auth::id())->value('id') ?? Employee::query()->value('id');

            $order = $this->orderService->createOrder($data);

            return $this->success(
                new OrderResource($order),
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create order: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get all orders for table
     * GET /api/v1/orders/table/{tableId}
     */
    public function getByTable(int $tableId)
    {
        try {
            $orders = Order::where('table_id', $tableId)
                ->with('items.food', 'payment')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return $this->success(
                OrderResource::collection($orders),
                'Orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get order details
     * GET /api/v1/orders/{orderId}
     */
    public function show(int $orderId)
    {
        try {
            $order = Order::with('items.food', 'payment', 'coupon')
                ->findOrFail($orderId);

            // Check authorization
            $this->authorize('view', $order);

            return $this->success(
                new OrderResource($order),
                'Order retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Order not found', 404);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve order: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Update order status
     * PUT /api/v1/orders/{orderId}/status
     */
    public function updateStatus(Request $request, int $orderId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,in_progress,ready,served,paid,cancelled'
            ]);

            $order = Order::findOrFail($orderId);
            $this->authorize('update', $order);

            $order = $this->orderService->updateOrderStatus($order, $request->status);

            return $this->success(
                new OrderResource($order),
                'Order status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update order status: ' . $e->getMessage(), 400);
        }
    }

    public function updateItemStatus(Request $request, int $orderId, int $itemId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,preparing,ready,served,cancelled'
            ]);

            $order = Order::with('items')->findOrFail($orderId);
            if ($request->status === 'served') {
                abort_unless($request->user()?->hasRole(['staff', 'chef', 'admin']), 403);
            } elseif ($request->status === 'cancelled') {
                $item = $order->items()->whereKey($itemId)->firstOrFail();
                abort_unless(
                    !in_array($order->status, ['paid', 'cancelled', 'served'], true)
                        && in_array($item->status, ['pending', 'confirmed'], true),
                    422,
                    'Only queue items can be cancelled before cooking starts'
                );
            } else {
                $this->authorize('update', $order);
            }

            $item = $order->items()->whereKey($itemId)->firstOrFail();
            $item->update(['status' => $request->status]);

            if ($request->status === 'cancelled') {
                $this->recalculateOrderTotal($order);
            }

            $this->syncOrderStatusFromItems($order);

            return $this->success(
                new OrderResource($order->fresh('items.food', 'payment')),
                'Order item status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update order item status: ' . $e->getMessage(), 400);
        }
    }

    private function recalculateOrderTotal(Order $order): void
    {
        $subtotal = $order->items()
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $subtotal * 0.1,
            'total_price' => $subtotal + ($subtotal * 0.1),
        ]);
    }

    private function syncOrderStatusFromItems(Order $order): void
    {
        if (in_array($order->status, ['paid', 'cancelled'], true)) {
            return;
        }

        $activeItems = $order->items()->where('status', '!=', 'cancelled');

        if (!(clone $activeItems)->exists()) {
            return;
        }

        if ((clone $activeItems)->where('status', 'preparing')->exists()) {
            $order->update([
                'status' => 'in_progress',
                'estimated_completion_time' => $order->estimated_completion_time ?? now()->addMinutes(15),
            ]);
            return;
        }

        if ((clone $activeItems)->whereIn('status', ['pending', 'confirmed'])->exists()) {
            if (!in_array($order->status, ['in_progress', 'ready', 'served'], true)) {
                $order->update(['status' => 'pending']);
            }
            return;
        }

        if ((clone $activeItems)->where('status', 'ready')->exists()) {
            $order->update([
                'status' => 'ready',
                'actual_completion_time' => $order->actual_completion_time ?? now(),
            ]);
            return;
        }

        $order->update([
            'status' => 'served',
            'actual_completion_time' => $order->actual_completion_time ?? now(),
        ]);
    }

    /**
     * Customer asks staff to come collect payment.
     * POST /api/v1/orders/{orderId}/request-payment
     */
    public function requestPayment(int $orderId)
    {
        try {
            $order = Order::whereIn('status', ['pending', 'confirmed', 'in_progress', 'ready', 'served'])
                ->findOrFail($orderId);

            $order->update(['payment_requested_at' => now()]);

            return $this->success(
                new OrderResource($order->fresh('items', 'payment')),
                'Payment request sent to staff'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to request payment: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Cancel order
     * DELETE /api/v1/orders/{orderId}
     */
    public function cancel(int $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $this->authorize('delete', $order);

            $order = $this->orderService->cancelOrder($order);

            return $this->success(
                new OrderResource($order),
                'Order cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to cancel order: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get order statistics (admin/manager only)
     * GET /api/v1/orders/stats/summary
     */
    public function getStats(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $stats = $this->orderService->getOrderStats(
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('table_id')
        );

        return $this->success($stats, 'Order statistics retrieved successfully');
    }
}
