<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order)
    {
        // User can view if they created the order or are admin
        return $user->id === $order->created_by_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user)
    {
        return $user->hasRole(['staff', 'chef', 'admin']);
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order)
    {
        return $user->hasRole(['chef', 'admin']);
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order)
    {
        // Only admin can delete orders, and only if not paid
        return $user->hasRole('admin') && !in_array($order->status, ['paid', 'served']);
    }

    /**
     * Determine whether the user can update order status.
     */
    public function updateStatus(User $user, Order $order)
    {
        return $user->hasRole(['chef', 'admin']);
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order)
    {
        return $user->hasRole(['staff', 'admin']) && !in_array($order->status, ['paid', 'served']);
    }
}
