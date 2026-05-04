<?php

namespace App\Services\Billing;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Coupon;
use App\Repositories\Billing\BillingRepository;

interface IBillingService
{
    public function processPayment($data);
    public function generateInvoice($order, $paymentId);
    public function calculateDiscount($order, $coupon);
}

class BillingService implements IBillingService
{
    protected $billingRepository;

    public function __construct(BillingRepository $billingRepository)
    {
        $this->billingRepository = $billingRepository;
    }

    public function processPayment($data)
    {
        $order = Order::findOrFail($data['order_id']);

        $payment = Payment::create([
            'order_id' => $data['order_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => 'completed',
            'transaction_id' => $this->generateTransactionId(),
            'reference_code' => $data['reference_code'] ?? null,
            'paid_at' => now(),
        ]);

        // Update order status
        $order->update(['status' => 'paid']);

        return $payment;
    }

    public function generateInvoice($order, $paymentId)
    {
        $payment = Payment::findOrFail($paymentId);

        $invoice = Invoice::create([
            'payment_id' => $paymentId,
            'invoice_number' => $this->generateInvoiceNumber(),
            'subtotal' => $order->total_price,
            'tax' => $this->calculateTax($order->total_price),
            'discount' => 0,
            'total' => $order->total_price,
            'issued_at' => now(),
            'status' => 'issued',
        ]);

        return $invoice;
    }

    public function calculateDiscount($order, $coupon)
    {
        if ($order->total_price < $coupon->min_order_amount) {
            return 0;
        }

        $discount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discount = ($order->total_price * $coupon->discount_value) / 100;
        } else {
            $discount = $coupon->discount_value;
        }

        if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
            $discount = $coupon->max_discount_amount;
        }

        return $discount;
    }

    private function generateTransactionId()
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }

    private function generateInvoiceNumber()
    {
        return 'INV' . date('YmdHis');
    }

    private function calculateTax($amount)
    {
        return ($amount * 10) / 100; // 10% tax
    }
}
