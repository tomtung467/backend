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

    public function getPayments(Request $request)
    {
        $query = Payment::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest();

        if ($request->boolean('summary')) {
            $payments = $query->limit((int) $request->query('limit', 10))
                ->get(['id', 'order_id', 'amount', 'payment_method', 'status', 'paid_at', 'created_at']);
        } else {
            $payments = $query->with(['order', 'invoice'])->get();
        }

        return response()->json($payments);
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string|in:cash,card,qr_code,digital_wallet',
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

    public function getCurrentBills(Request $request)
    {
        $orders = Order::with(['table:id,table_number,section', 'items.food:id,name'])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->when($request->filled('table_id'), fn ($query) => $query->where('table_id', $request->query('table_id')))
            ->latest()
            ->get([
                'id',
                'order_number',
                'table_id',
                'status',
                'subtotal',
                'tax_amount',
                'discount_amount',
                'total_price',
                'payment_requested_at',
                'created_at',
            ]);

        return response()->json($orders);
    }

    public function getInvoices(Request $request)
    {
        $invoices = Invoice::with('payment.order')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->get();

        return response()->json($invoices);
    }

    public function applyCoupon(Request $request, $orderId)
    {
        $validated = $request->validate([
            'coupon_code' => 'nullable|string',
            'code' => 'nullable|string',
        ]);

        $couponCode = $validated['coupon_code'] ?? $validated['code'] ?? null;

        $coupon = Coupon::where('code', $couponCode)
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
