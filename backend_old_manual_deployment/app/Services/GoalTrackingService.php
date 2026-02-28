<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * GoalTrackingService
 *
 * Single responsibility: Track business goal progress and generate prescriptive actions.
 * Returns facts only — no decisions, no side effects beyond explicit updates.
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-004
 */
class GoalTrackingService
{
    /**
     * Get all active business goals.
     *
     * @return array Array of goal records
     */
    public function getActiveGoals(): array
    {
        return DB::table('business_goals')
            ->whereIn('status', ['active', 'on_track', 'at_risk', 'behind'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($goal) {
                return [
                    'id'                   => $goal->id,
                    'goal_type'            => $goal->goal_type,
                    'period'               => $goal->period,
                    'target_value'         => (float) $goal->target_value,
                    'current_value'        => (float) $goal->current_value,
                    'status'               => $goal->status,
                    'progress_percentage'  => (float) $goal->progress_percentage,
                    'prescriptive_actions' => $goal->prescriptive_actions
                        ? json_decode($goal->prescriptive_actions, true)
                        : [],
                    'created_by'  => $goal->created_by,
                    'created_at'  => $goal->created_at,
                    'updated_at'  => $goal->updated_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get goal details by ID.
     *
     * @param  int $goalId
     * @return array|null Goal record or null if not found
     */
    public function getGoalById(int $goalId): ?array
    {
        $goal = DB::table('business_goals')->find($goalId);

        if (!$goal) {
            return null;
        }

        return [
            'id'                   => $goal->id,
            'goal_type'            => $goal->goal_type,
            'period'               => $goal->period,
            'target_value'         => (float) $goal->target_value,
            'current_value'        => (float) $goal->current_value,
            'status'               => $goal->status,
            'progress_percentage'  => (float) $goal->progress_percentage,
            'prescriptive_actions' => $goal->prescriptive_actions
                ? json_decode($goal->prescriptive_actions, true)
                : [],
            'created_by'  => $goal->created_by,
            'created_at'  => $goal->created_at,
            'updated_at'  => $goal->updated_at,
        ];
    }

    /**
     * Calculate progress for a single goal.
     *
     * Progress = (current_value / target_value) × 100, clamped to 0-100.
     * Status is derived from progress:
     *   >= 100  → achieved
     *   >= 70   → on_track
     *   >= 40   → at_risk
     *   < 40    → behind
     *
     * @param  int $goalId
     * @return array{
     *   goal_id: int,
     *   target_value: float,
     *   current_value: float,
     *   progress_percentage: float,
     *   status: string
     * }
     */
    public function calculateGoalProgress(int $goalId): array
    {
        $goal = DB::table('business_goals')->find($goalId);

        if (!$goal) {
            return [
                'goal_id'            => $goalId,
                'target_value'       => 0.0,
                'current_value'      => 0.0,
                'progress_percentage'=> 0.0,
                'status'             => 'behind',
            ];
        }

        $targetValue  = (float) $goal->target_value;
        $currentValue = (float) $goal->current_value;

        $progress = $targetValue > 0
            ? min(100.0, round(($currentValue / $targetValue) * 100, 2))
            : 0.0;

        if ($progress >= 100) {
            $status = 'achieved';
        } elseif ($progress >= 70) {
            $status = 'on_track';
        } elseif ($progress >= 40) {
            $status = 'at_risk';
        } else {
            $status = 'behind';
        }

        return [
            'goal_id'            => $goalId,
            'target_value'       => $targetValue,
            'current_value'      => $currentValue,
            'progress_percentage'=> $progress,
            'status'             => $status,
        ];
    }

    /**
     * Generate prescriptive actions for a goal based on its progress gap.
     *
     * Actions are simple rule-based suggestions — no AI involved.
     * Example: "Gap: $50K, Action: Close Opportunity #12 ($45K)"
     *
     * @param  int $goalId
     * @return array{
     *   goal_id: int,
     *   gap: float,
     *   actions: array
     * }
     */
    public function generatePrescriptiveActions(int $goalId): array
    {
        $goal = DB::table('business_goals')->find($goalId);

        if (!$goal) {
            return ['goal_id' => $goalId, 'gap' => 0.0, 'actions' => []];
        }

        $targetValue  = (float) $goal->target_value;
        $currentValue = (float) $goal->current_value;
        $gap          = round($targetValue - $currentValue, 2);

        $actions = [];

        if ($gap <= 0) {
            $actions[] = ['type' => 'info', 'message' => 'Goal already achieved. Target: ' . $targetValue . ', Current: ' . $currentValue];
            return ['goal_id' => $goalId, 'gap' => $gap, 'actions' => $actions];
        }

        // Suggest top open opportunities that could close the gap
        $openOpportunities = DB::table('opportunities')
            ->whereNotIn('stage', ['won', 'lost'])
            ->where('estimated_value', '>', 0)
            ->orderBy('estimated_value', 'desc')
            ->select('id', 'client', 'estimated_value', 'stage', 'probability')
            ->limit(5)
            ->get();

        $remainingGap = $gap;
        foreach ($openOpportunities as $opp) {
            if ($remainingGap <= 0) {
                break;
            }
            $actions[] = [
                'type'    => 'close_opportunity',
                'message' => sprintf(
                    'Gap remaining: %.2f — Close Opportunity #%d (%s, %.2f, %s stage)',
                    $remainingGap,
                    $opp->id,
                    $opp->client,
                    $opp->estimated_value,
                    $opp->stage
                ),
                'opportunity_id'    => $opp->id,
                'opportunity_value' => (float) $opp->estimated_value,
            ];
            $remainingGap -= $opp->estimated_value;
        }

        if (empty($actions)) {
            $actions[] = [
                'type'    => 'general',
                'message' => sprintf('Gap: %.2f — No open opportunities found. Consider creating new leads.', $gap),
            ];
        }

        return ['goal_id' => $goalId, 'gap' => $gap, 'actions' => $actions];
    }

    /**
     * Recalculate and persist progress for all active goals.
     *
     * @return array{updated_count: int, goals: array}
     */
    public function updateGoalProgress(): array
    {
        $goals = DB::table('business_goals')
            ->whereIn('status', ['active', 'on_track', 'at_risk', 'behind'])
            ->get();

        $updatedCount = 0;
        $updatedGoals = [];

        foreach ($goals as $goal) {
            $progress = $this->calculateGoalProgress($goal->id);
            $actions  = $this->generatePrescriptiveActions($goal->id);

            DB::table('business_goals')
                ->where('id', $goal->id)
                ->update([
                    'progress_percentage'  => $progress['progress_percentage'],
                    'status'               => $progress['status'],
                    'prescriptive_actions' => json_encode($actions['actions']),
                    'updated_at'           => now(),
                ]);

            $updatedCount++;
            $updatedGoals[] = [
                'id'                   => $goal->id,
                'goal_type'            => $goal->goal_type,
                'progress_percentage'  => $progress['progress_percentage'],
                'status'               => $progress['status'],
            ];
        }

        return [
            'updated_count' => $updatedCount,
            'goals'         => $updatedGoals,
        ];
    }

    /**
     * Create a new business goal.
     *
     * @param  array $data  Validated goal data
     * @param  int   $userId  Authenticated user ID
     * @return array Created goal record
     */
    public function createGoal(array $data, int $userId): array
    {
        $goalId = DB::table('business_goals')->insertGetId([
            'goal_type'            => $data['goal_type'],
            'period'               => $data['period'],
            'target_value'         => $data['target_value'],
            'current_value'        => $data['current_value'] ?? 0,
            'status'               => 'active',
            'progress_percentage'  => 0,
            'prescriptive_actions' => null,
            'created_by'           => $userId,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return $this->getGoalById($goalId) ?? [];
    }
}
