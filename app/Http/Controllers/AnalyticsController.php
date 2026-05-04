<?php

namespace App\Http\Controllers;

use App\Models\KPISnapshot;
use App\Models\SalesReport;
use App\Models\Order;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function getDashboardData(Request $request)
    {
        $period = $request->query('period', 'today'); // today, week, month, year
        $data = $this->analyticsService->getDashboardData($period);
        return response()->json($data);
    }

    public function getRevenueData(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $data = $this->analyticsService->getRevenueData($startDate, $endDate);
        return response()->json($data);
    }

    public function getTopDishes(Request $request)
    {
        $limit = $request->query('limit', 10);
        $period = $request->query('period', 'month');

        $topDishes = $this->analyticsService->getTopDishes($limit, $period);
        return response()->json($topDishes);
    }

    public function getOrderAnalytics(Request $request)
    {
        $period = $request->query('period', 'month');
        $analytics = Order::selectRaw('DATE(created_at) as date, COUNT(*) as total_orders, SUM(total_price) as total_revenue')
            ->groupBy('date')
            ->get();

        return response()->json($analytics);
    }

    public function getEmployeePerformance(Request $request)
    {
        $period = $request->query('period', 'month');
        $data = $this->analyticsService->getEmployeePerformance($period);
        return response()->json($data);
    }

    public function getSalesReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $report = $this->analyticsService->generateSalesReport($validated);
        return response()->json($report);
    }

    public function getPaymentMethodBreakdown(Request $request)
    {
        $period = $request->query('period', 'month');
        $data = $this->analyticsService->getPaymentMethodBreakdown($period);
        return response()->json($data);
    }

    public function getCustomerMetrics(Request $request)
    {
        $metrics = $this->analyticsService->getCustomerMetrics();
        return response()->json($metrics);
    }
}
