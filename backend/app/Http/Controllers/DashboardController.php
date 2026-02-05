<?php

namespace App\Http\Controllers;

use App\Services\DashboardSummaryService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardSummaryService $dashboardSummaryService
    ) {}

    public function getSummary()
    {
        try {
            $summary = $this->dashboardSummaryService->getSummary();
            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function projectProgress(int $id)
    {
        return view('dashboard.project-progress', ['projectId' => $id]);
    }

    public function paymentGap(int $id)
    {
        return view('dashboard.payment-gap', ['projectId' => $id]);
    }

    public function projectHealth(int $id)
    {
        return view('dashboard.project-health', ['projectId' => $id]);
    }

    public function cashFlow()
    {
        return view('dashboard.cash-flow');
    }

    public function upcomingExpenses()
    {
        return view('dashboard.upcoming-expenses');
    }

    public function salesPipeline()
    {
        return view('dashboard.sales-pipeline');
    }
}
