<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Milestone Management Service
 * 
 * Single responsibility: Handle CRUD operations for payment milestones with immutability enforcement.
 * Paid milestones cannot be edited or deleted to maintain financial record integrity.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class MilestoneManagementService
{
    /**
     * Get all milestones for a project ordered chronologically.
     * 
     * @param int $projectId
     * @return array Array of milestone records
     */
    public function getMilestonesByProject(int $projectId): array
    {
        $milestones = DB::table('payment_milestones')
            ->where('project_id', $projectId)
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($milestone) {
                return [
                    'id' => $milestone->id,
                    'project_id' => $milestone->project_id,
                    'name' => $milestone->name,
                    'amount' => (float) $milestone->amount,
                    'currency' => $milestone->currency,
                    'status' => $milestone->status,
                    'due_date' => $milestone->due_date,
                    'created_at' => $milestone->created_at,
                    'updated_at' => $milestone->updated_at,
                    'is_paid' => $milestone->status === 'paid',
                ];
            })
            ->toArray();

        return $milestones;
    }

    /**
     * Get milestone details by ID.
     * 
     * @param int $milestoneId
     * @return array|null Milestone record or null if not found
     */
    public function getMilestoneDetails(int $milestoneId): ?array
    {
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->first();

        if (!$milestone) {
            return null;
        }

        return [
            'id' => $milestone->id,
            'project_id' => $milestone->project_id,
            'name' => $milestone->name,
            'amount' => (float) $milestone->amount,
            'currency' => $milestone->currency,
            'status' => $milestone->status,
            'due_date' => $milestone->due_date,
            'created_at' => $milestone->created_at,
            'updated_at' => $milestone->updated_at,
            'is_paid' => $milestone->status === 'paid',
        ];
    }

    /**
     * Create a new milestone for a project.
     * 
     * @param int $projectId
     * @param array $data Milestone data (name, amount, currency, status, due_date)
     * @return array ['success' => bool, 'message' => string, 'milestone_id' => int|null]
     */
    public function createMilestone(int $projectId, array $data): array
    {
        try {
            $milestoneId = DB::table('payment_milestones')->insertGetId([
                'project_id' => $projectId,
                'name' => $data['name'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => $data['status'] ?? 'pending',
                'due_date' => $data['due_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Milestone created successfully.',
                'milestone_id' => $milestoneId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create milestone: ' . $e->getMessage(),
                'milestone_id' => null,
            ];
        }
    }

    /**
     * Update an existing milestone.
     * 
     * Enforces immutability: Paid milestones cannot be edited.
     * 
     * @param int $milestoneId
     * @param array $data Updated milestone data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateMilestone(int $milestoneId, array $data): array
    {
        // Check if milestone exists
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->first();

        if (!$milestone) {
            return [
                'success' => false,
                'message' => 'Milestone not found.',
            ];
        }

        // Enforce immutability: paid milestones cannot be edited
        if ($milestone->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Cannot edit paid milestones. Financial records are immutable.',
            ];
        }

        try {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'status' => $data['status'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'updated_at' => now(),
            ], function ($value) {
                return $value !== null;
            });

            DB::table('payment_milestones')
                ->where('id', $milestoneId)
                ->update($updateData);

            return [
                'success' => true,
                'message' => 'Milestone updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update milestone: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a milestone.
     * 
     * Enforces immutability: Paid milestones cannot be deleted.
     * 
     * @param int $milestoneId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMilestone(int $milestoneId): array
    {
        // Check if milestone exists
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->first();

        if (!$milestone) {
            return [
                'success' => false,
                'message' => 'Milestone not found.',
            ];
        }

        // Enforce immutability: paid milestones cannot be deleted
        if ($milestone->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Cannot delete paid milestones. Financial records are immutable.',
            ];
        }

        try {
            DB::table('payment_milestones')
                ->where('id', $milestoneId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Milestone deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete milestone: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get the total amount for milestones by status and currency.
     * 
     * @param int $projectId
     * @return array ['currencies' => ['USD' => ['pending' => float, 'invoiced' => float, 'paid' => float, 'total' => float]]]
     */
    public function getMilestonesSummary(int $projectId): array
    {
        $milestones = DB::table('payment_milestones')
            ->where('project_id', $projectId)
            ->select('status', 'currency', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('status', 'currency')
            ->get();

        $currencies = [];

        foreach ($milestones as $milestone) {
            $currency = $milestone->currency;
            
            if (!isset($currencies[$currency])) {
                $currencies[$currency] = [
                    'pending' => 0.0,
                    'invoiced' => 0.0,
                    'paid' => 0.0,
                    'total' => 0.0,
                ];
            }

            $amount = (float) $milestone->total;
            $currencies[$currency][$milestone->status] = $amount;
            $currencies[$currency]['total'] += $amount;
        }

        return [
            'currencies' => $currencies,
        ];
    }
}
