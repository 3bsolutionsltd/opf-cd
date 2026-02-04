<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * PaymentGapService
 * 
 * Calculates the Payment Gap for projects.
 * 
 * Definitions:
 * - earned_value = contract_value × progress / 100
 * - received_value = sum of inflow cash transactions
 * - gap_amount = earned_value − received_value
 * - gap_percentage = (gap_amount / contract_value) × 100
 * 
* Notes:
 * - This service reports payment gap facts only.
 * - Risk classification is handled by ProjectHealthService.
 * - Payment received percentage = (Total Paid / Contract Value) × 100
 * - Project progress is calculated by ProjectProgressService
 * 
 * Source: docs/_truth.md
 */
class PaymentGapService
{
    private ProjectProgressService $progressService;

    public function __construct(ProjectProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Calculate payment gap for a project
     * 
     * @param int $projectId
     * @return array ['gap_amount' => float, 'gap_percentage' => float, 
     *                'progress' => float, 'earned_value' => float, 'received_value' => float, 
     *                'contract_value' => float]
     * @throws InvalidArgumentException
     */
    public function calculatePaymentGap(int $projectId): array
    {
        $project = DB::table('projects')
            ->where('id', $projectId)
            ->first(['id', 'contract_value', 'contract_currency']);

        if (!$project) {
            throw new InvalidArgumentException("Project with ID {$projectId} not found");
        }

        if ($project->contract_value <= 0) {
            throw new InvalidArgumentException("Project {$projectId} has invalid contract_value: {$project->contract_value}");
        }

        $progress = $this->progressService->calculateProjectProgress($projectId);
        $earnedValue = $project->contract_value * ($progress / 100);

        $receivedValue = DB::table('cash_transactions')
            ->join('payment_milestones', function ($join) {
                $join->on('cash_transactions.source_id', '=', 'payment_milestones.id')
                     ->where('cash_transactions.source_type', '=', 'project_payment');
            })
            ->where('payment_milestones.project_id', $projectId)
            ->where('cash_transactions.type', 'inflow')
            ->sum('cash_transactions.amount');

        $gapAmount = $earnedValue - $receivedValue;
        $gapPercentage = ($gapAmount / $project->contract_value) * 100;
        $gapPercentage = max(-100, min(100, $gapPercentage));

        return [
            'gap_amount' => round($gapAmount, 2),
            'gap_percentage' => round($gapPercentage, 2),
            'progress' => round($progress, 2),
            'earned_value' => round($earnedValue, 2),
            'received_value' => round((float) $receivedValue, 2),
            'contract_value' => (float) $project->contract_value,
        ];
    }
}
