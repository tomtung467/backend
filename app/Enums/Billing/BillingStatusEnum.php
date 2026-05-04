<?php
namespace App\Enums\Billing;

enum BillingStatusEnum: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
}
