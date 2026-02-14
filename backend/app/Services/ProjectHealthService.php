<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;

/**
 * ProjectHealthService
 * 
 * Synthesizes project health status by aggregating signals from multiple services
 * and applying weighted factor scoring model.
 * 
 * Rules:
 * - Does NOT recalculate metrics (delegates to specialized services)
 * - Performs judgment and synthesis only
 * - Scoring model: PHI = (time_score × 0.3) + (payment_score × 0.3) + 
 *                        (blocker_score × 0.2) + (overdue_score × 0.2)
 * - Health bands: Green (≥80), Yellow (50-79), Red (<50)
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
     * Calculate project health index (PHI) with weighted factor scoring
     * 
     * Formula from docs/_truth.md:
     * PHI Score = (time_score × 0.3) + (payment_score × 0.3) + 
     *             (blocker_score × 0.2) + (overdue_score × 0.2)
     * 
     * @param int $projectId
     * @return array [
     *   'project_id' => int,
     *   'health_status' => string (green|amber|red),
     *   'score' => int (0-100),
     *   'signals' => array (key numeric inputs),
     *   'reasons' => array (factor explanations),
     *   'details' => array (human-readable breakdown),
     *   'recommendations' => array (actionable suggestions)
     * ]
     */
    public function getProjectHealth(int $projectId): array
    {
        // Get project details
        $project = DB::table('projects')->where('id', $projectId)->first();
        if (!$project) {
            throw new \InvalidArgumentException("Project not found: {$projectId}");
        }

        // Gather signals from specialized services
        $paymentGap = $this->paymentGapService->calculatePaymentGap($projectId);
        $projectProgress = $this->progressService->calculateProjectProgress($projectId);

        $paymentGapPercentage = $paymentGap['gap_percentage'];
        
        // Initialize tracking arrays
        $reasons = [];
        $details = [];
        $recommendations = [];

        // === FACTOR 1: TIME SCORE (30%) ===
        // Compare actual progress to expected progress based on time elapsed
        $timeScore = $this->calculateTimeScore($project, $projectProgress, $details, $recommendations);

        // === FACTOR 2: PAYMENT SCORE (30%) ===
        // Measure payment gap (work delivered vs payment received)
        $paymentScore = $this->calculatePaymentScore($paymentGapPercentage, $paymentGap, $details, $recommendations);

        // === FACTOR 3: BLOCKER SCORE (20%) ===
        // Count blocked tasks and apply penalty
        $blockerScore = $this->calculateBlockerScore($projectId, $details, $recommendations);

        // === FACTOR 4: OVERDUE SCORE (20%) ===
        // Count overdue payment milestones
        $overdueScore = $this->calculateOverdueScore($projectId, $details, $recommendations);

        // === CALCULATE PHI SCORE ===
        // Apply weighted formula from _truth.md
        $phiScore = ($timeScore * 0.3) + ($paymentScore * 0.3) + 
                    ($blockerScore * 0.2) + ($overdueScore * 0.2);
        
        // Round and clamp to 0-100
        $phiScore = round($phiScore);
        $phiScore = max(0, min(100, $phiScore));

        // Build reasons based on factor scores
        if ($timeScore < 60) {
            $reasons[] = "Time score below 60 - project behind schedule";
        }
        if ($paymentScore < 60) {
            $reasons[] = "Payment score below 60 - significant payment gap";
        }
        if ($blockerScore < 80) {
            $reasons[] = "Blocker score reduced - tasks are blocked";
        }
        if ($overdueScore < 80) {
            $reasons[] = "Overdue score reduced - milestones past due";
        }

        // Determine health status
        if ($phiScore >= 80) {
            $healthStatus = 'green';
            $statusLabel = 'Healthy';
            $statusDescription = 'Project is performing well with no major concerns.';
        } elseif ($phiScore >= 50) {
            $healthStatus = 'amber';
            $statusLabel = 'At Risk';
            $statusDescription = 'Project has some concerns that need attention.';
        } else {
            $healthStatus = 'red';
            $statusLabel = 'Critical';
            $statusDescription = 'Project requires immediate action to address serious issues.';
        }

        return [
            'project_id' => $projectId,
            'health_status' => $healthStatus,
            'status_label' => $statusLabel,
            'status_description' => $statusDescription,
            'score' => $phiScore,
            'signals' => [
                'time_score' => round($timeScore, 1),
                'payment_score' => round($paymentScore, 1),
                'blocker_score' => round($blockerScore, 1),
                'overdue_score' => round($overdueScore, 1),
                'payment_gap_percentage' => round($paymentGapPercentage, 2),
                'payment_gap_amount' => round($paymentGap['gap_amount'], 2),
                'earned_value' => round($paymentGap['earned_value'], 2),
                'received_value' => round($paymentGap['received_value'], 2),
                'project_progress' => $projectProgress,
                'project_status' => $project->status,
            ],
            'reasons' => $reasons,
            'details' => $details,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Calculate time score (0-100): actual progress vs expected progress
     * 
     * Expected progress = (days elapsed / total project days) × 100
     * Time score = (actual_progress / expected_progress) × 100
     * 
     * Returns FACT ONLY - no decisions.
     */
    private function calculateTimeScore($project, float $actualProgress, array &$details, array &$recommendations): float
    {
        $today = new DateTime();
        $startDate = new DateTime($project->start_date);
        $endDate = new DateTime($project->end_date);

        // Calculate total project duration
        $totalDays = $startDate->diff($endDate)->days;
        
        // Calculate days elapsed
        $daysElapsed = $startDate->diff($today)->days;

        // If project hasn't started yet
        if ($daysElapsed < 0) {
            $details[] = "Project has not started yet.";
            return 100;
        }

        // If project is past end date
        if ($today > $endDate) {
            $daysOverdue = $endDate->diff($today)->days;
            $details[] = sprintf(
                "Project is %d days past deadline. Expected 100%% complete, actual: %.1f%%.",
                $daysOverdue,
                $actualProgress
            );
            if ($actualProgress < 100) {
                $recommendations[] = "Project is overdue - expedite remaining tasks";
            }
            // Score based on how far from 100% we are
            return max(0, 100 - (100 - $actualProgress));
        }

        // Calculate expected progress
        $expectedProgress = ($daysElapsed / $totalDays) * 100;

        // Calculate time score
        if ($expectedProgress == 0) {
            $timeScore = 100;
        } else {
            $timeScore = ($actualProgress / $expectedProgress) * 100;
        }

        // Clamp to 0-100
        $timeScore = max(0, min(100, $timeScore));

        // Add details
        $details[] = sprintf(
            "Progress vs Time: %.1f%% complete, expected %.1f%% by now (%.1f%% of timeline elapsed).",
            $actualProgress,
            $expectedProgress,
            ($daysElapsed / $totalDays) * 100
        );

        if ($timeScore < 80) {
            $recommendations[] = "Project is behind schedule - review task allocation and deadlines";
        }

        return $timeScore;
    }

    /**
     * Calculate payment score (0-100): based on payment gap
     * 
     * If gap is positive (owed money): reduce score
     * If gap is negative (ahead on payment): full score
     * 
     * Returns FACT ONLY - no decisions.
     */
    private function calculatePaymentScore(float $paymentGapPercentage, array $paymentGap, array &$details, array &$recommendations): float
    {
        if ($paymentGapPercentage > 0) {
            // We're owed money - reduce score based on gap size
            $paymentScore = max(0, 100 - abs($paymentGapPercentage));
            
            $details[] = sprintf(
                "Payment behind work: %.0f%% gap. Earned %s but received %s.",
                abs($paymentGapPercentage),
                number_format($paymentGap['earned_value'], 0),
                number_format($paymentGap['received_value'], 0)
            );

            if ($paymentGapPercentage > 40) {
                $recommendations[] = "Critical payment delay - send immediate payment reminder to client";
                $recommendations[] = "Consider pausing work until payment received";
            } elseif ($paymentGapPercentage > 20) {
                $recommendations[] = "Follow up with client on outstanding payment";
            }
        } elseif ($paymentGapPercentage < -20) {
            // Payment significantly ahead of work
            $paymentScore = 100;
            $details[] = sprintf(
                "Payment ahead of work by %.0f%%. Received %s but delivered %s in value.",
                abs($paymentGapPercentage),
                number_format($paymentGap['received_value'], 0),
                number_format($paymentGap['earned_value'], 0)
            );
            $recommendations[] = "Prioritize delivery to match payment received";
        } else {
            // Payment tracking well
            $paymentScore = 100;
            $details[] = sprintf(
                "Payment tracking well: %.0f%% gap.",
                abs($paymentGapPercentage)
            );
        }

        return $paymentScore;
    }

    /**
     * Calculate blocker score (0-100): based on blocked tasks count
     * 
     * Blocker score = 100 - (blocked_count × penalty_per_block)
     * Penalty: 10 points per blocked task
     * 
     * Returns FACT ONLY - no decisions.
     */
    private function calculateBlockerScore(int $projectId, array &$details, array &$recommendations): float
    {
        // Count blocked tasks for this project
        $blockedCount = DB::table('tasks')
            ->where('project_id', $projectId)
            ->where('status', 'blocked')
            ->count();

        // Apply penalty: 10 points per blocked task
        $blockerScore = 100 - ($blockedCount * 10);
        $blockerScore = max(0, $blockerScore);

        if ($blockedCount > 0) {
            $details[] = sprintf("%d task(s) blocked - impacting project velocity.", $blockedCount);
            $recommendations[] = "Resolve blocked tasks to improve project health";
        } else {
            $details[] = "No tasks currently blocked.";
        }

        return $blockerScore;
    }

    /**
     * Calculate overdue score (0-100): based on overdue milestones count
     * 
     * Overdue score = 100 - (overdue_count × penalty_per_overdue)
     * Penalty: 15 points per overdue milestone
     * 
     * Returns FACT ONLY - no decisions.
     */
    private function calculateOverdueScore(int $projectId, array &$details, array &$recommendations): float
    {
        $today = date('Y-m-d');

        // Count overdue milestones (past due date and not paid)
        $overdueCount = DB::table('payment_milestones')
            ->where('project_id', $projectId)
            ->where('due_date', '<', $today)
            ->where('status', '!=', 'paid')
            ->count();

        // Apply penalty: 15 points per overdue milestone
        $overdueScore = 100 - ($overdueCount * 15);
        $overdueScore = max(0, $overdueScore);

        if ($overdueCount > 0) {
            $details[] = sprintf("%d payment milestone(s) overdue.", $overdueCount);
            $recommendations[] = "Follow up on overdue payments immediately";
        } else {
            $details[] = "No payment milestones overdue.";
        }

        return $overdueScore;
    }
}
