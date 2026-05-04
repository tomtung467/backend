<?php

namespace App\Repositories\Orders;

use App\Models\Order;
use App\Repositories\BaseRepository;

interface IOrderRepository
{
    public function getActiveOrders();
    public function getOrdersByStatus($status);
    public function createOrder($data);
    public function updateOrderStatus($id, $status);
}

class OrderRepository extends BaseRepository implements IOrderRepository
{
    protected $model = Order::class;

    public function getActiveOrders()
    {
        return Order::whereIn('status', ['pending', 'cooking', 'ready'])
            ->with('orderItems.food')
            ->get();
    }

    public function getOrdersByStatus($status)
    {
        return Order::where('status', $status)->get();
    }

    public function createOrder($data)
    {
        return Order::create($data);
    }

    public function updateOrderStatus($id, $status)
    {
        $order = $this->find($id);
        $order->update(['status' => $status]);
        return $order;
    }
}
