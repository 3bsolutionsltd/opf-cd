<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * DashboardSummaryService
 * 
 * Provides high-level summary metrics for the landing page dashboard.
 * Aggregates key statistics across projects, finance, and sales.
 * 
 * All calculations follow business rules defined in docs/_truth.md
 * 
 * Source: docs/_truth.md
 */
class DashboardSummaryService
{
    public function __construct(
        private ProjectHealthService $projectHealthService,
        private CashFlowService $cashFlowService,
        private AlertService $alertService
    ) {}

    /**
     * Get dashboard summary with key metrics
     * 
     * Returns FACTS ONLY - no decision logic.
     * 
     * @return array [
     *   'total_projects' => int,
     *   'active_projects' => int,
     *   'cash_at_hand' => float,
     *   'total_pipeline_value' => float,
     *   'total_upcoming_expenses' => float,
     *   'health_green_count' => int,
     *   'health_red_count' => int,
     *   'health_amber_count' => int,
     *   'projects_at_risk' => int,
     *   'currency' => string
     * ]
     */
    public function getSummary(): array
    {
        // Total and active projects
        $totalProjects = DB::table('projects')->count();
        $activeProjects = DB::table('projects')
            ->where('status', 'active')
            ->count();

        // Cash at hand calculation
        $totalOpeningBalance = DB::table('accounts')
            ->sum('opening_balance') ?? 0;

        $totalInflows = DB::table('cash_transactions')
            ->where('type', 'inflow')
            ->sum('amount') ?? 0;

        $totalOutflows = DB::table('cash_transactions')
            ->where('type', 'outflow')
            ->sum('amount') ?? 0;

        $cashAtHand = $totalOpeningBalance + $totalInflows - $totalOutflows;

        // Total pipeline value per currency
        $pipelineUGX = DB::table('opportunities')
            ->where('currency', 'UGX')
            ->sum('estimated_value') ?? 0;
        
        $pipelineUSD = DB::table('opportunities')
            ->where('currency', 'USD')
            ->sum('estimated_value') ?? 0;

        // Upcoming expenses (next 90 days)
        $ninetyDaysFromNow = date('Y-m-d', strtotime('+90 days'));
        $totalUpcomingExpenses = DB::table('expenses')
            ->where('due_date', '<=', $ninetyDaysFromNow)
            ->where('status', '!=', 'paid')
            ->sum('amount') ?? 0;

        // Project health summary - calculate health for active projects only
        $activeProjectIds = DB::table('projects')
            ->where('status', 'active')
            ->pluck('id');

        $healthStatuses = [];
        $projectsAtRisk = 0;

        foreach ($activeProjectIds as $projectId) {
            $healthData = $this->projectHealthService->getProjectHealth((int) $projectId);
            $status = $healthData['health_status'] ?? 'amber';
            
            $healthStatuses[] = $status;
            
            if ($status === 'red') {
                $projectsAtRisk++;
            }
        }

        // Count health statuses - return FACTS ONLY, no decision logic
        $greenCount = count(array_filter($healthStatuses, fn($s) => $s === 'green'));
        $redCount = count(array_filter($healthStatuses, fn($s) => $s === 'red'));
        $amberCount = count(array_filter($healthStatuses, fn($s) => $s === 'amber'));

        // Default currency
        $currency = 'USD';

        // Calculate burn rate and runway
        $burnRate = $this->cashFlowService->calculateMonthlyBurnRate($currency);
        $cashRunway = $this->cashFlowService->calculateCashRunway($currency);

        // Get active alerts count
        $alertCount = $this->alertService->getTotalAlertCount();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'cash_at_hand' => round($cashAtHand, 2),
            'burn_rate' => $burnRate,
            'cash_runway_months' => $cashRunway,
            'pipeline_ugx' => round($pipelineUGX, 2),
            'pipeline_usd' => round($pipelineUSD, 2),
            'total_upcoming_expenses' => round($totalUpcomingExpenses, 2),
            'health_green_count' => $greenCount,
            'health_red_count' => $redCount,
            'health_amber_count' => $amberCount,
            'projects_at_risk' => $projectsAtRisk,
            'currency' => $currency,
            'alert_count' => $alertCount,
        ];
    }
}
