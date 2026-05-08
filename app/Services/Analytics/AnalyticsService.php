<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Ingredient;
use App\Models\InventoryLog;
use App\Models\KPISnapshot;
use App\Models\SalesReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

interface IAnalyticsService
{
    public function getDashboardData($period);
    public function getRevenueData($startDate, $endDate);
    public function getTopDishes($limit, $period, $startDate = null, $endDate = null);
}

class AnalyticsService implements IAnalyticsService
{
    public function getDashboardData($period)
    {
        $dateRange = $this->getDateRange($period);

        return [
            'total_revenue' => $this->getTotalRevenue($dateRange),
            'total_orders' => $this->getTotalOrders($dateRange),
            'avg_order_value' => $this->getAverageOrderValue($dateRange),
            'top_dishes' => $this->getTopDishes(5, $period),
            'payment_breakdown' => $this->getPaymentBreakdown($dateRange),
        ];
    }

    public function getRevenueData($startDate, $endDate)
    {
        $dateRange = $this->resolveDateRange(null, $startDate, $endDate);

        return Order::selectRaw('DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as orders')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getTopDishes($limit, $period = 'month', $startDate = null, $endDate = null)
    {
        $dateRange = $this->resolveDateRange($period, $startDate, $endDate);

        return OrderItem::selectRaw('food_id, foods.name, SUM(order_items.quantity) as total_quantity, COUNT(DISTINCT order_items.order_id) as orders_count, SUM(order_items.total_price) as revenue')
            ->join('foods', 'order_items.food_id', '=', 'foods.id')
            ->whereBetween('order_items.created_at', $dateRange)
            ->groupBy('food_id', 'foods.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    public function getOrderAnalytics($startDate = null, $endDate = null, $status = null)
    {
        $dateRange = $this->resolveDateRange('month', $startDate, $endDate);

        return Order::selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, SUM(total_price) as total_revenue')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getInventoryAnalytics($startDate = null, $endDate = null)
    {
        $dateRange = $this->resolveDateRange('month', $startDate, $endDate);

        $items = Ingredient::select([
                'id',
                'name',
                'category',
                'unit',
                'current_quantity',
                'min_quantity',
                'max_quantity',
                'unit_cost',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Ingredient $ingredient) {
                $stockValue = (float) $ingredient->current_quantity * (float) $ingredient->unit_cost;

                return [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'category' => $ingredient->category,
                    'unit' => $ingredient->unit,
                    'current_quantity' => (float) $ingredient->current_quantity,
                    'min_quantity' => (float) $ingredient->min_quantity,
                    'max_quantity' => (float) $ingredient->max_quantity,
                    'unit_cost' => (float) $ingredient->unit_cost,
                    'stock_value' => $stockValue,
                    'is_low_stock' => (float) $ingredient->current_quantity <= (float) $ingredient->min_quantity,
                ];
            });

        $movements = InventoryLog::selectRaw(
                'DATE(inventory_logs.created_at) as date, COUNT(*) as movements, SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as stock_in, SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as stock_out'
            )
            ->whereBetween('inventory_logs.created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $categoryValue = $items
            ->groupBy('category')
            ->map(fn ($group, $category) => [
                'category' => $category ?: 'general',
                'items_count' => $group->count(),
                'stock_value' => $group->sum('stock_value'),
                'low_stock_count' => $group->where('is_low_stock', true)->count(),
            ])
            ->values();

        return [
            'summary' => [
                'total_items' => $items->count(),
                'low_stock_items' => $items->where('is_low_stock', true)->count(),
                'total_stock_value' => $items->sum('stock_value'),
                'movements' => $movements->sum('movements'),
                'stock_in' => $movements->sum('stock_in'),
                'stock_out' => $movements->sum('stock_out'),
            ],
            'items' => $items,
            'category_value' => $categoryValue,
            'movements_by_day' => $movements,
        ];
    }

    public function getEmployeePerformance($period)
    {
        $dateRange = $this->getDateRange($period);

        return KPISnapshot::selectRaw('employee_id, SUM(orders_processed) as total_orders, AVG(avg_processing_time) as avg_time, AVG(customer_satisfaction_score) as satisfaction')
            ->whereBetween('date', $dateRange)
            ->groupBy('employee_id')
            ->get();
    }

    public function generateSalesReport($data)
    {
        $report = SalesReport::create([
            'report_type' => $data['report_type'],
            'period_start' => $data['start_date'],
            'period_end' => $data['end_date'],
            'total_revenue' => $this->getTotalRevenue([$data['start_date'], $data['end_date']]),
            'total_orders' => $this->getTotalOrders([$data['start_date'], $data['end_date']]),
            'avg_order_value' => $this->getAverageOrderValue([$data['start_date'], $data['end_date']]) ?? 0,
            'data' => json_encode($this->getDetailedReportData($data)),
        ]);

        return $report;
    }

    public function getPaymentMethodBreakdown($period)
    {
        $dateRange = $this->getDateRange($period);

        return Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('payment_method')
            ->get();
    }

    public function getCustomerMetrics()
    {
        return [
            'total_customers' => Order::distinct('table_id')->count(),
            'new_customers_today' => Order::where('created_at', '>=', now()->startOfDay())->distinct('table_id')->count(),
            'repeat_customers' => Order::selectRaw('COUNT(DISTINCT table_id) as table_count')
                ->havingRaw('COUNT(DISTINCT table_id) > 1')
                ->count(),
        ];
    }

    private function getTotalRevenue($dateRange)
    {
        return Order::whereBetween('created_at', $this->normalizeDateRange($dateRange))
            ->sum('total_price');
    }

    private function getTotalOrders($dateRange)
    {
        return Order::whereBetween('created_at', $this->normalizeDateRange($dateRange))->count();
    }

    private function getAverageOrderValue($dateRange)
    {
        return Order::whereBetween('created_at', $this->normalizeDateRange($dateRange))->avg('total_price');
    }

    private function getPaymentBreakdown($dateRange)
    {
        return Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween('created_at', $this->normalizeDateRange($dateRange))
            ->groupBy('payment_method')
            ->get();
    }

    private function getDetailedReportData($data)
    {
        return [
            'revenue_by_day' => $this->getRevenueData($data['start_date'], $data['end_date']),
            'top_dishes' => $this->getTopDishes(10, null, $data['start_date'], $data['end_date']),
            'orders_by_day' => $this->getOrderAnalytics($data['start_date'], $data['end_date']),
            'inventory' => $data['report_type'] === 'inventory'
                ? $this->getInventoryAnalytics($data['start_date'], $data['end_date'])
                : null,
            'payment_methods' => $this->getPaymentBreakdown([$data['start_date'], $data['end_date']]),
        ];
    }

    private function resolveDateRange($period = null, $startDate = null, $endDate = null)
    {
        if ($startDate && $endDate) {
            return $this->normalizeDateRange([$startDate, $endDate]);
        }

        return $this->getDateRange($period ?: 'month');
    }

    private function normalizeDateRange($dateRange)
    {
        return [
            Carbon::parse($dateRange[0])->startOfDay(),
            Carbon::parse($dateRange[1])->endOfDay(),
        ];
    }

    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'year':
                return [now()->startOfYear(), now()->endOfYear()];
            default:
                return [now()->startOfDay(), now()->endOfDay()];
        }
    }
}
