<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Opportunity Management Service
 * 
 * Single responsibility: Handle CRUD operations for sales opportunities.
 * No decision logic, no business rules - just facts.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class OpportunityManagementService
{
    private AuditService $auditService;
    private OpportunityProjectService $opportunityProjectService;

    public function __construct(
        AuditService $auditService,
        OpportunityProjectService $opportunityProjectService
    ) {
        $this->auditService = $auditService;
        $this->opportunityProjectService = $opportunityProjectService;
    }
    /**
     * Get all opportunities ordered by expected close date.
     * 
     * @return array Array of opportunity records
     */
    public function getOpportunities(): array
    {
        $opportunities = DB::table('opportunities')
            ->select(
                'opportunities.*',
                'users.email as owner_email'
            )
            ->leftJoin('users', 'opportunities.owner', '=', 'users.id')
            ->orderBy('expected_close_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'client' => $opportunity->client,
                    'description' => $opportunity->description,
                    'estimated_value' => (float) $opportunity->estimated_value,
                    'currency' => $opportunity->currency,
                    'probability' => (float) $opportunity->probability,
                    'stage' => $opportunity->stage,
                    'source' => $opportunity->source,
                    'owner' => $opportunity->owner,
                    'owner_email' => $opportunity->owner_email,
                    'expected_close_date' => $opportunity->expected_close_date,
                    'created_at' => $opportunity->created_at,
                    'updated_at' => $opportunity->updated_at,
                ];
            })
            ->toArray();

        return $opportunities;
    }

    /**
     * Get opportunity details by ID.
     * 
     * @param int $opportunityId
     * @return array|null Opportunity record or null if not found
     */
    public function getOpportunityDetails(int $opportunityId): ?array
    {
        $opportunity = DB::table('opportunities')
            ->select(
                'opportunities.*',
                'users.email as owner_email'
            )
            ->leftJoin('users', 'opportunities.owner', '=', 'users.id')
            ->where('opportunities.id', $opportunityId)
            ->first();

        if (!$opportunity) {
            return null;
        }

        return [
            'id' => $opportunity->id,
            'client' => $opportunity->client,
            'description' => $opportunity->description,
            'estimated_value' => (float) $opportunity->estimated_value,
            'currency' => $opportunity->currency,
            'probability' => (float) $opportunity->probability,
            'stage' => $opportunity->stage,
            'source' => $opportunity->source,
            'owner' => $opportunity->owner,
            'owner_email' => $opportunity->owner_email,
            'expected_close_date' => $opportunity->expected_close_date,
            'created_at' => $opportunity->created_at,
            'updated_at' => $opportunity->updated_at,
        ];
    }

    /**
     * Create a new opportunity.
     * 
     * @param array $data Opportunity data (client, description, estimated_value, currency, probability, stage, source, owner, expected_close_date)
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string, 'opportunity_id' => int|null]
     */
    public function createOpportunity(array $data, int $userId, ?Request $request = null): array
    {
        try {
            $opportunityId = DB::table('opportunities')->insertGetId([
                'client' => $data['client'],
                'description' => $data['description'],
                'estimated_value' => $data['estimated_value'],
                'currency' => $data['currency'] ?? 'UGX',
                'probability' => $data['probability'],
                'stage' => $data['stage'] ?? 'lead',
                'source' => $data['source'],
                'owner' => $data['owner'],
                'expected_close_date' => $data['expected_close_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'opportunities',
                $opportunityId,
                array_merge($data, ['id' => $opportunityId]),
                $request
            );

            return [
                'success' => true,
                'message' => 'Opportunity created successfully.',
                'opportunity_id' => $opportunityId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create opportunity: ' . $e->getMessage(),
                'opportunity_id' => null,
            ];
        }
    }

    /**
     * Update an existing opportunity.
     * 
     * @param int $opportunityId
     * @param array $data Updated opportunity data
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateOpportunity(int $opportunityId, array $data, int $userId, ?Request $request = null): array
    {
        // Check if opportunity exists
        $opportunity = DB::table('opportunities')
            ->where('id', $opportunityId)
            ->first();

        if (!$opportunity) {
            return [
                'success' => false,
                'message' => 'Opportunity not found.',
            ];
        }

        // Store before state for audit log
        $before = (array) $opportunity;

        try {
            $updateData = array_filter([
                'client' => $data['client'] ?? null,
                'description' => $data['description'] ?? null,
                'estimated_value' => $data['estimated_value'] ?? null,
                'currency' => $data['currency'] ?? null,
                'probability' => $data['probability'] ?? null,
                'stage' => $data['stage'] ?? null,
                'source' => $data['source'] ?? null,
                'owner' => $data['owner'] ?? null,
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'updated_at' => now(),
            ], function ($value) {
                return $value !== null;
            });

            DB::table('opportunities')
                ->where('id', $opportunityId)
                ->update($updateData);

            // Get after state for audit log
            $after = (array) DB::table('opportunities')->where('id', $opportunityId)->first();

            // Log audit trail
            $this->auditService->logUpdate(
                $userId,
                'opportunities',
                $opportunityId,
                $before,
                $after,
                $request
            );

            // Check if stage changed to 'won' and handle project creation
            $stageChanged = ($before['stage'] ?? null) !== ($after['stage'] ?? null);
            $isNowWon = ($after['stage'] ?? null) === 'won';
            
            $message = 'Opportunity updated successfully.';
            $projectCreationResult = null;

            if ($stageChanged && $isNowWon) {
                // Check if projects already exist for this opportunity
                $existingProjects = DB::table('projects')
                    ->where('opportunity_id', $opportunityId)
                    ->select('id', 'name', 'status')
                    ->get();

                if ($existingProjects->isEmpty()) {
                    // No projects exist - create first one automatically
                    $projectCreationResult = $this->opportunityProjectService->createProjectFromOpportunity(
                        $opportunityId,
                        $userId,
                        $request
                    );

                    if ($projectCreationResult['success']) {
                        $message .= ' Project created automatically (ID: ' . $projectCreationResult['project_id'] . ').';
                    } else {
                        $message .= ' Warning: Failed to auto-create project - ' . $projectCreationResult['message'];
                    }
                } else {
                    // Projects already exist - don't create duplicate
                    $projectCount = $existingProjects->count();
                    $projectIds = $existingProjects->pluck('id')->toArray();
                    
                    $message .= ' Opportunity marked as won. ' . $projectCount . ' existing project(s) found (IDs: ' . implode(', ', $projectIds) . ').';
                    
                    $projectCreationResult = [
                        'success' => true,
                        'project_id' => null,
                        'existing_projects' => $projectIds,
                        'message' => 'Projects already exist for this opportunity'
                    ];
                }
            }

            return [
                'success' => true,
                'message' => $message,
                'project_created' => isset($projectCreationResult['project_id']) && $projectCreationResult['project_id'] !== null,
                'project_id' => $projectCreationResult['project_id'] ?? null,
                'existing_projects' => $projectCreationResult['existing_projects'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update opportunity: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an opportunity.
     * 
     * @param int $opportunityId
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteOpportunity(int $opportunityId, int $userId, ?Request $request = null): array
    {
        // Check if opportunity exists
        $opportunity = DB::table('opportunities')
            ->where('id', $opportunityId)
            ->first();

        if (!$opportunity) {
            return [
                'success' => false,
                'message' => 'Opportunity not found.',
            ];
        }

        // Store final state for audit log
        $deletedData = (array) $opportunity;

        try {
            DB::table('opportunities')
                ->where('id', $opportunityId)
                ->delete();

            // Log audit trail
            $this->auditService->logDelete(
                $userId,
                'opportunities',
                $opportunityId,
                $deletedData,
                $request
            );

            return [
                'success' => true,
                'message' => 'Opportunity deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete opportunity: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Manually create a project linked to an opportunity.
     * 
     * Delegates to OpportunityProjectService to handle multi-phase scenarios
     * where multiple projects need to be created from a single opportunity.
     * 
     * @param int $opportunityId The opportunity to link to
     * @param array $projectData Project details from user
     * @param int $userId User creating the project
     * @param Request|null $request Request for audit trail
     * @return array Result from project creation
     */
    public function createProjectFromOpportunity(int $opportunityId, array $projectData, int $userId, ?Request $request = null): array
    {
        return $this->opportunityProjectService->createManualProject(
            $opportunityId,
            $projectData,
            $userId,
            $request
        );
    }

    /**
     * Get all projects linked to an opportunity.
     * 
     * @param int $opportunityId The opportunity ID
     * @return array List of projects
     */
    public function getProjectsForOpportunity(int $opportunityId): array
    {
        return $this->opportunityProjectService->getProjectsForOpportunity($opportunityId);
    }
}
