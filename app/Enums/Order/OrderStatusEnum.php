<?php
namespace App\Enums\Order;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case COOKING = 'cooking';
    case SERVED = 'served';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
