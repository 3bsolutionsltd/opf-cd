<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectManagementService
{
    /**
     * Get all projects.
     * 
     * Returns array of all projects with basic details.
     * 
     * @return array
     */
    public function getAllProjects(): array
    {
        $projects = DB::table('projects')
            ->select(
                'id',
                'name',
                'client',
                'contract_value',
                'contract_currency',
                'start_date',
                'end_date',
                'status',
                'project_lead_id',
                'created_at',
                'updated_at'
            )
            ->orderBy('created_at', 'desc')
            ->get();

        return $projects->toArray();
    }

    /**
     * Get project details by ID.
     * 
     * Returns project data or null if not found.
     * 
     * @param int $projectId
     * @return array|null
     */
    public function getProjectDetails(int $projectId): ?array
    {
        $project = DB::table('projects')
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            return null;
        }

        return (array) $project;
    }

    /**
     * Create new project.
     * 
     * Returns success fact with project ID or validation error.
     * 
     * @param array $data
     * @return array ['success' => bool, 'project_id' => int|null, 'message' => string]
     */
    public function createProject(array $data): array
    {
        try {
            $projectId = DB::table('projects')->insertGetId([
                'name' => $data['name'],
                'client' => $data['client'],
                'contract_value' => $data['contract_value'],
                'contract_currency' => $data['contract_currency'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'] ?? 'planned',
                'project_lead_id' => $data['project_lead_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Project created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'project_id' => null,
                'message' => 'Failed to create project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing project.
     * 
     * Enforces immutability: cannot change contract_value if payments received.
     * Returns success fact or validation error.
     * 
     * @param int $projectId
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProject(int $projectId, array $data): array
    {
        // Check if project exists
        $project = DB::table('projects')->where('id', $projectId)->first();
        if (!$project) {
            return [
                'success' => false,
                'message' => 'Project not found'
            ];
        }

        // Check if attempting to change contract value
        if (isset($data['contract_value']) && $data['contract_value'] != $project->contract_value) {
            // Check if any payments have been received
            $paymentsReceived = DB::table('payment_milestones')
                ->where('project_id', $projectId)
                ->where('is_paid', true)
                ->exists();

            if ($paymentsReceived) {
                return [
                    'success' => false,
                    'message' => 'Cannot change contract value: payments have been received'
                ];
            }
        }

        try {
            $updateData = [
                'updated_at' => now()
            ];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['client'])) {
                $updateData['client'] = $data['client'];
            }
            if (isset($data['contract_value'])) {
                $updateData['contract_value'] = $data['contract_value'];
            }
            if (isset($data['contract_currency'])) {
                $updateData['contract_currency'] = $data['contract_currency'];
            }
            if (isset($data['start_date'])) {
                $updateData['start_date'] = $data['start_date'];
            }
            if (isset($data['end_date'])) {
                $updateData['end_date'] = $data['end_date'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }
            if (isset($data['project_lead_id'])) {
                $updateData['project_lead_id'] = $data['project_lead_id'];
            }

            DB::table('projects')
                ->where('id', $projectId)
                ->update($updateData);

            return [
                'success' => true,
                'message' => 'Project updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete project.
     * 
     * Enforces immutability: cannot delete project with paid milestones.
     * Returns success fact or validation error.
     * 
     * @param int $projectId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteProject(int $projectId): array
    {
        // Check if project exists
        $project = DB::table('projects')->where('id', $projectId)->first();
        if (!$project) {
            return [
                'success' => false,
                'message' => 'Project not found'
            ];
        }

        // Check for paid milestones
        $hasPaidMilestones = DB::table('payment_milestones')
            ->where('project_id', $projectId)
            ->where('is_paid', true)
            ->exists();

        if ($hasPaidMilestones) {
            return [
                'success' => false,
                'message' => 'Cannot delete project: paid milestones exist'
            ];
        }

        try {
            DB::table('projects')
                ->where('id', $projectId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Project deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if project has received payments.
     * 
     * Returns true if any milestone is paid, false otherwise.
     * 
     * @param int $projectId
     * @return bool
     */
    public function hasReceivedPayments(int $projectId): bool
    {
        return DB::table('payment_milestones')
            ->where('project_id', $projectId)
            ->where('is_paid', true)
            ->exists();
    }
}
