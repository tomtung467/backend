<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\Food;
use App\Models\AuditLog;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    private $inventoryService;

    public function __construct(
        InventoryService $inventoryService
    ) {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create new order with items
     *
     * @param array $data
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Validate table exists and is available
            $table = Table::findOrFail($data['table_id']);

            if ($table->status !== 'empty' && $table->status !== 'occupied') {
                throw new \Exception('Table is not available');
            }

            // Create order
            $order = new Order();
            $order->table_id = $data['table_id'];
            $order->order_number = $this->generateOrderNumber();
            $order->status = 'pending';
            $order->customer_notes = $data['customer_notes'] ?? null;
            $order->special_requests = $data['special_requests'] ?? null;
            $order->created_by_id = $data['created_by_id'];
            $order->source = $data['source'] ?? 'table';
            $order->save();

            // Add order items
            $totalPrice = 0;
            foreach ($data['items'] as $item) {
                $food = Food::findOrFail($item['food_id']);

                if (!$food->is_available) {
                    throw new \Exception("Food item {$food->name} is not available");
                }

                $itemPrice = $food->price * $item['quantity'];
                $totalPrice += $itemPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'food_id' => $item['food_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $food->price,
                    'total_price' => $itemPrice,
                    'special_notes' => $item['notes'] ?? null,
                    'status' => 'pending'
                ]);
            }

            // Calculate totals
            $subtotal = $totalPrice;
            $tax = $subtotal * 0.1; // 10% VAT
            $total = $subtotal + $tax;

            $order->subtotal = $subtotal;
            $order->tax_amount = $tax;
            $order->total_price = $total;
            $order->save();

            // Update table status
            $table->status = 'occupied';
            $table->occupied_since = now();
            $table->save();

            // Trigger events
            event(new OrderCreated($order));

            // Log audit
            $this->logAudit('create', 'Order', $order->id, null, $order->toArray());

            return $order->fresh()->load('items', 'table');
        });
    }

    /**
     * Update order status
     *
     * @param Order $order
     * @param string $status
     * @return Order
     */
    public function updateOrderStatus(Order $order, string $status)
    {
        return DB::transaction(function () use ($order, $status) {
            $oldStatus = $order->status;
            $order->status = $status;

            // Update estimated/actual times
            if ($status === 'in_progress') {
                $order->estimated_completion_time = now()->addMinutes(15);
            } elseif ($status === 'ready' || $status === 'served') {
                $order->actual_completion_time = now();
            }

            $order->save();

            // Trigger event
            event(new OrderStatusChanged($order, $oldStatus));

            // Log audit
            $this->logAudit('update', 'Order', $order->id, ['status' => $oldStatus], ['status' => $status]);

            return $order;
        });
    }

    /**
     * Cancel order and restore table status
     *
     * @param Order $order
     * @return Order
     */
    public function cancelOrder(Order $order)
    {
        return DB::transaction(function () use ($order) {
            if (in_array($order->status, ['paid', 'served'])) {
                throw new \Exception('Cannot cancel paid or served order');
            }

            $order->status = 'cancelled';
            $order->save();

            // Mark all items as cancelled
            $order->items()->update(['status' => 'cancelled']);

            // Restore table status if no other active orders
            $activeOrders = Order::where('table_id', $order->table_id)
                ->whereNotIn('status', ['cancelled', 'paid'])
                ->count();

            if ($activeOrders === 0) {
                $table = Table::find($order->table_id);
                $table->status = 'empty';
                $table->occupied_since = null;
                $table->save();
            }

            // Log audit
            $this->logAudit('delete', 'Order', $order->id, $order->toArray(), ['status' => 'cancelled']);

            return $order;
        });
    }

    /**
     * Get order statistics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param int|null $tableId
     * @return array
     */
    public function getOrderStats($dateFrom = null, $dateTo = null, $tableId = null)
    {
        $query = Order::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($tableId) {
            $query->where('table_id', $tableId);
        }

        $stats = [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_price'),
            'average_order_value' => $query->avg('total_price'),
            'completed_orders' => $query->whereIn('status', ['served', 'paid'])->count(),
            'cancelled_orders' => $query->where('status', 'cancelled')->count(),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'average_items_per_order' => OrderItem::whereIn(
                'order_id',
                $query->pluck('id')
            )->avg('quantity'),
        ];

        return $stats;
    }

    /**
     * Generate unique order number
     * Format: ORD-20260425-001
     */
    private function generateOrderNumber()
    {
        $date = date('Ymd');
        $lastOrder = Order::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (int)substr($lastOrder->order_number, -3) + 1 : 1;

        return 'ORD-' . $date . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Log audit trail
     */
    private function logAudit($action, $modelType, $modelId, $oldValues, $newValues)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);
    }
}
