<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\AuditService;

/**
 * OpportunityProjectService
 * 
 * Single Responsibility: Create projects from won opportunities
 * 
 * This service handles the automatic conversion of opportunities to projects
 * when an opportunity's stage changes to "won". It maps opportunity fields
 * to project fields according to business rules defined in _truth.md.
 */
class OpportunityProjectService
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Create a project from a won opportunity.
     * 
     * Maps opportunity fields to project fields:
     * - client: copied from opportunity
     * - contract_value: set to estimated_value (editable)
     * - contract_currency: set to opportunity currency
     * - start_date: set to current date
     * - end_date: NULL (requires manual entry)
     * - status: "planned"
     * - project_lead_id: NULL (requires manual assignment)
     * - opportunity_id: foreign key to opportunity
     * 
     * Since 'name' is required but not in opportunities table, the project
     * name will be auto-generated from client name with a timestamp to ensure uniqueness.
     * 
     * @param int $opportunityId The ID of the won opportunity
     * @param int $userId The user who triggered the conversion
     * @param Request|null $request Optional request for audit trail
     * @return array ['success' => bool, 'project_id' => int|null, 'message' => string]
     */
    public function createProjectFromOpportunity(int $opportunityId, int $userId, ?Request $request = null): array
    {
        try {
            // Fetch the opportunity
            $opportunity = DB::table('opportunities')
                ->where('id', $opportunityId)
                ->first();

            if (!$opportunity) {
                return [
                    'success' => false,
                    'project_id' => null,
                    'message' => 'Opportunity not found'
                ];
            }

            // Verify opportunity is actually won
            if ($opportunity->stage !== 'won') {
                return [
                    'success' => false,
                    'project_id' => null,
                    'message' => 'Can only create projects from won opportunities'
                ];
            }

            // Auto-generate project name from client and timestamp
            $projectName = $opportunity->client . ' - Project (' . date('Y-m-d H:i') . ')';

            // Create the project with mapped fields
            $projectId = DB::table('projects')->insertGetId([
                'name' => $projectName,
                'client' => $opportunity->client,
                'contract_value' => $opportunity->estimated_value,
                'contract_currency' => $opportunity->currency,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => null,
                'status' => 'planned',
                'project_lead_id' => null,
                'opportunity_id' => $opportunityId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'projects',
                $projectId,
                [
                    'id' => $projectId,
                    'name' => $projectName,
                    'client' => $opportunity->client,
                    'contract_value' => $opportunity->estimated_value,
                    'contract_currency' => $opportunity->currency,
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => null,
                    'status' => 'planned',
                    'project_lead_id' => null,
                    'opportunity_id' => $opportunityId,
                    'source' => 'auto_created_from_won_opportunity'
                ],
                $request
            );

            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Project created successfully from won opportunity'
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
     * Get projects linked to an opportunity.
     * 
     * Returns list of projects that were spawned from a specific opportunity.
     * 
     * @param int $opportunityId
     * @return array List of project records
     */
    public function getProjectsForOpportunity(int $opportunityId): array
    {
        return DB::table('projects')
            ->where('opportunity_id', $opportunityId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Manually create a project linked to an opportunity.
     * 
     * Unlike auto-creation, this method:
     * - Does NOT check if opportunity is won (works for any stage)
     * - Accepts user-provided project details
     * - Supports multi-phase opportunities (multiple projects per opportunity)
     * 
     * Required fields in $projectData:
     * - name: Project name (user provides)
     * - contract_value: Numeric value
     * - contract_currency: Currency code
     * - start_date: Y-m-d format
     * 
     * Optional fields in $projectData:
     * - end_date: Y-m-d format (nullable)
     * - status: planned|active|on_hold|completed|cancelled (default: planned)
     * - project_lead_id: User ID (nullable)
     * 
     * @param int $opportunityId The ID of the opportunity
     * @param array $projectData Project details from user input
     * @param int $userId The user creating the project
     * @param Request|null $request Optional request for audit trail
     * @return array ['success' => bool, 'project_id' => int|null, 'message' => string]
     */
    public function createManualProject(int $opportunityId, array $projectData, int $userId, ?Request $request = null): array
    {
        try {
            // Verify opportunity exists
            $opportunity = DB::table('opportunities')
                ->where('id', $opportunityId)
                ->first();

            if (!$opportunity) {
                return [
                    'success' => false,
                    'project_id' => null,
                    'message' => 'Opportunity not found'
                ];
            }

            // Validate required fields
            $requiredFields = ['name', 'contract_value', 'contract_currency', 'start_date'];
            foreach ($requiredFields as $field) {
                if (!isset($projectData[$field]) || $projectData[$field] === null || $projectData[$field] === '') {
                    return [
                        'success' => false,
                        'project_id' => null,
                        'message' => "Missing required field: {$field}"
                    ];
                }
            }

            // Prepare project data with defaults
            $insertData = [
                'name' => $projectData['name'],
                'client' => $opportunity->client,  // Always use client from opportunity
                'contract_value' => $projectData['contract_value'],
                'contract_currency' => $projectData['contract_currency'],
                'start_date' => $projectData['start_date'],
                'end_date' => $projectData['end_date'] ?? null,
                'status' => $projectData['status'] ?? 'planned',
                'project_lead_id' => $projectData['project_lead_id'] ?? null,
                'opportunity_id' => $opportunityId,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Create the project
            $projectId = DB::table('projects')->insertGetId($insertData);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'projects',
                $projectId,
                array_merge($insertData, [
                    'id' => $projectId,
                    'source' => 'manual_creation_from_opportunity'
                ]),
                $request
            );

            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Project created successfully and linked to opportunity'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'project_id' => null,
                'message' => 'Failed to create project: ' . $e->getMessage()
            ];
        }
    }
}
