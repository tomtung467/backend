<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\KPISnapshot;
use App\Models\SalesReport;
use Illuminate\Support\Facades\DB;

interface IAnalyticsService
{
    public function getDashboardData($period);
    public function getRevenueData($startDate, $endDate);
    public function getTopDishes($limit, $period);
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
        return Order::selectRaw('DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as orders')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get();
    }

    public function getTopDishes($limit, $period)
    {
        $dateRange = $this->getDateRange($period);

        return OrderItem::selectRaw('food_id, foods.name, COUNT(*) as quantity_sold, SUM(order_items.total_price) as revenue')
            ->join('foods', 'order_items.food_id', '=', 'foods.id')
            ->whereBetween('order_items.created_at', $dateRange)
            ->groupBy('food_id', 'foods.name')
            ->orderByDesc('quantity_sold')
            ->limit($limit)
            ->get();
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
            'total_orders' => Order::whereBetween('created_at', [$data['start_date'], $data['end_date']])->count(),
            'avg_order_value' => $this->getAverageOrderValue([$data['start_date'], $data['end_date']]),
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
        return Order::whereBetween('created_at', $dateRange)
            ->sum('total_price');
    }

    private function getTotalOrders($dateRange)
    {
        return Order::whereBetween('created_at', $dateRange)->count();
    }

    private function getAverageOrderValue($dateRange)
    {
        return Order::whereBetween('created_at', $dateRange)->avg('total_price');
    }

    private function getPaymentBreakdown($dateRange)
    {
        return Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween('created_at', $dateRange)
            ->groupBy('payment_method')
            ->get();
    }

    private function getDetailedReportData($data)
    {
        return [
            'revenue_by_day' => $this->getRevenueData($data['start_date'], $data['end_date']),
            'top_dishes' => $this->getTopDishes(10, 'custom'),
            'payment_methods' => $this->getPaymentBreakdown([$data['start_date'], $data['end_date']]),
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
