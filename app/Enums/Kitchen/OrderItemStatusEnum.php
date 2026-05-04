<?php
namespace App\Enums\Kitchen;

enum OrderItemStatusEnum: string
{
    case PENDING = 'pending';
    case COOKING = 'cooking';
    case READY = 'ready';
    case SERVED = 'served';
    case CANCELLED = 'cancelled';
}
