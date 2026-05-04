<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\Billing\BillingService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string|in:cash,card,qr_code,bank_transfer',
            'reference_code' => 'nullable|string',
        ]);

        $payment = $this->billingService->processPayment($validated);
        return response()->json($payment);
    }

    public function getPaymentDetails($id)
    {
        $payment = Payment::with('invoice', 'order')->findOrFail($id);
        return response()->json($payment);
    }

    public function getOrderPayments($orderId)
    {
        $payments = Payment::where('order_id', $orderId)->get();
        return response()->json($payments);
    }

    public function generateInvoice(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $order = Order::findOrFail($orderId);
        $invoice = $this->billingService->generateInvoice($order, $validated['payment_id']);

        return response()->json($invoice);
    }

    public function getInvoice($id)
    {
        $invoice = Invoice::findOrFail($id);
        return response()->json($invoice);
    }

    public function applyCoupon(Request $request, $orderId)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $validated['coupon_code'])
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code'], 400);
        }

        $order = Order::findOrFail($orderId);
        $discount = $this->billingService->calculateDiscount($order, $coupon);

        return response()->json([
            'discount' => $discount,
            'coupon' => $coupon,
        ]);
    }

    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', $validated['code'])
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->first();

        return response()->json([
            'valid' => $coupon !== null,
            'coupon' => $coupon,
        ]);
    }
}
