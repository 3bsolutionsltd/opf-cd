<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;

/**
 * ProjectHealthService
 * 
 * Synthesizes project health status by aggregating signals from multiple services
 * and applying a penalty-based scoring model.
 * 
 * Rules:
 * - Does NOT recalculate metrics (delegates to specialized services)
 * - Performs judgment and synthesis only
 * - Scoring model: starts at 100, applies penalties, clamps to 0-100
 * - Health bands: Green (80+), Amber (50-79), Red (<50)
 * 
 * Source: docs/_truth.md
 */
class ProjectHealthService
{
    private ProjectProgressService $progressService;
    private PaymentGapService $paymentGapService;
    private CashFlowService $cashFlowService;
    private PipelineForecastService $pipelineService;
    private ExpenseSchedulerService $expenseService;

    public function __construct(
        ProjectProgressService $progressService,
        PaymentGapService $paymentGapService,
        CashFlowService $cashFlowService,
        PipelineForecastService $pipelineService,
        ExpenseSchedulerService $expenseService
    ) {
        $this->progressService = $progressService;
        $this->paymentGapService = $paymentGapService;
        $this->cashFlowService = $cashFlowService;
        $this->pipelineService = $pipelineService;
        $this->expenseService = $expenseService;
    }

    /**
     * Calculate project health index (PHI) with penalty-based scoring
     * 
     * @param int $projectId
     * @return array [
     *   'project_id' => int,
     *   'health_status' => string (green|amber|red),
     *   'score' => int (0-100),
     *   'signals' => array (key numeric inputs),
     *   'reasons' => array (human-readable penalty explanations)
     * ]
     */
    public function getProjectHealth(int $projectId): array
    {
        // Gather signals from all services
        $paymentGap = $this->paymentGapService->calculatePaymentGap($projectId);
        $projectProgress = $this->progressService->calculateProjectProgress($projectId);
        $cashFlow = $this->cashFlowService->getCashFlowSnapshot();
        $pipeline = $this->pipelineService->getPipelineForecast();
        $upcomingExpenses = $this->expenseService->getUpcomingExpenses();

        // Get project status
        $project = DB::table('projects')->where('id', $projectId)->first();
        if (!$project) {
            throw new \InvalidArgumentException("Project not found: {$projectId}");
        }
        
        $paymentGapPercentage = $paymentGap['gap_percentage'];
        $cashRunwayMonths = $cashFlow['cash_runway_months'];
        $weightedPipelineValue = $pipeline['weighted_pipeline_value'];

        // Count expenses in next 30 days
        $today = new DateTime();
        $thirtyDaysOut = (clone $today)->add(new DateInterval('P30D'));
        $expensesNext30Days = array_filter($upcomingExpenses, function ($expense) use ($today, $thirtyDaysOut) {
            $dueDate = new DateTime($expense['due_date']);
            return $dueDate >= $today && $dueDate <= $thirtyDaysOut;
        });
        $expenseCount30Days = count($expensesNext30Days);

        // Apply scoring model
        $score = 100;
        $reasons = [];

        // Payment gap penalties
        if ($paymentGapPercentage > 40) {
            $score -= 40;
            $reasons[] = "Payment gap exceeds 40% of earned value";
        } elseif ($paymentGapPercentage > 20) {
            $score -= 25;
            $reasons[] = "Payment gap exceeds 20% of earned value";
        }

        // Project progress penalty
        if ($projectProgress < 50 && $project->status === 'active') {
            $score -= 15;
            $reasons[] = "Project progress below 50% while active";
        }

        // Cash runway penalties
        if ($cashRunwayMonths < 1) {
            $score -= 50;
            $reasons[] = "Cash runway below 1 month";
        } elseif ($cashRunwayMonths < 3) {
            $score -= 30;
            $reasons[] = "Cash runway below 3 months";
        }

        // Pipeline penalty
        if ($weightedPipelineValue == 0) {
            $score -= 20;
            $reasons[] = "No weighted pipeline value";
        }

        // Upcoming expenses penalty
        if ($expenseCount30Days > 5) {
            $score -= 15;
            $reasons[] = "More than 5 expenses due in next 30 days";
        }

        // Clamp score
        $score = max(0, min(100, $score));

        // Determine health status
        if ($score >= 80) {
            $healthStatus = 'green';
        } elseif ($score >= 50) {
            $healthStatus = 'amber';
        } else {
            $healthStatus = 'red';
        }

        return [
            'project_id' => $projectId,
            'health_status' => $healthStatus,
            'score' => $score,
            'signals' => [
                'payment_gap_percentage' => round($paymentGapPercentage, 2),
                'project_progress' => $projectProgress,
                'project_status' => $project->status,
                'cash_runway_months' => $cashRunwayMonths,
                'weighted_pipeline_value' => $weightedPipelineValue,
                'expenses_next_30_days' => $expenseCount30Days,
            ],
            'reasons' => $reasons,
        ];
    }
}
