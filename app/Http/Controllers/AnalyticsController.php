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
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $topDishes = $this->analyticsService->getTopDishes($limit, $period, $startDate, $endDate);
        return response()->json($topDishes);
    }

    public function getOrderAnalytics(Request $request)
    {
        $analytics = $this->analyticsService->getOrderAnalytics(
            $request->query('start_date'),
            $request->query('end_date'),
            $request->query('status')
        );

        return response()->json($analytics);
    }

    public function getInventoryAnalytics(Request $request)
    {
        $analytics = $this->analyticsService->getInventoryAnalytics(
            $request->query('start_date'),
            $request->query('end_date')
        );

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
