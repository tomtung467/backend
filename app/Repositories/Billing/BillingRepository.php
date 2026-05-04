<?php

namespace App\Repositories\Billing;

use App\Models\Payment;
use App\Models\Invoice;
use App\Repositories\BaseRepository;

interface IBillingRepository
{
    public function createPayment($data);
    public function getPaymentsByOrder($orderId);
    public function createInvoice($data);
}

class BillingRepository extends BaseRepository implements IBillingRepository
{
    protected $model = Payment::class;

    public function createPayment($data)
    {
        return Payment::create($data);
    }

    public function getPaymentsByOrder($orderId)
    {
        return Payment::where('order_id', $orderId)->get();
    }

    public function createInvoice($data)
    {
        return Invoice::create($data);
    }
}
