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

    /**
     * Create project from template (Phase 5.4)
     * 
     * Creates a project AND automatically populates it with template tasks.
     * This is the core feature for Phase 5.4 - Project Templates & Workplan Generation.
     * 
     * Workflow:
     * 1. Create project from opportunity
     * 2. Get template tasks
     * 3. Create tasks for the project (atomically)
     * 4. Return complete project with all tasks
     * 
     * @param int $opportunityId The ID of the won opportunity
     * @param int $templateId The ID of the project template
     * @param int $userId The user who triggered the conversion
     * @param Request|null $request Optional request for audit trail
     * @return array ['success' => bool, 'project' => array|null, 'tasks_count' => int, 'message' => string]
     */
    public function createProjectWithTemplate(int $opportunityId, int $templateId, int $userId, ?Request $request = null): array
    {
        $projectId = null;
        $tasksCreated = 0;

        try {
            // Use transaction for atomicity - all succeed or all fail
            DB::beginTransaction();

            // Fetch the opportunity
            $opportunity = DB::table('opportunities')
                ->where('id', $opportunityId)
                ->first();

            if (!$opportunity) {
                DB::rollBack();
                return [
                    'success' => false,
                    'project' => null,
                    'tasks_count' => 0,
                    'message' => 'Opportunity not found'
                ];
            }

            // Verify opportunity is won
            if ($opportunity->stage !== 'won') {
                DB::rollBack();
                return [
                    'success' => false,
                    'project' => null,
                    'tasks_count' => 0,
                    'message' => 'Can only create projects from won opportunities'
                ];
            }

            // Verify template exists
            $template = DB::table('project_templates')
                ->where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                DB::rollBack();
                return [
                    'success' => false,
                    'project' => null,
                    'tasks_count' => 0,
                    'message' => 'Template not found or is inactive'
                ];
            }

            // Get template tasks
            $templateTasks = DB::table('project_template_tasks')
                ->where('project_template_id', $templateId)
                ->orderBy('phase_number')
                ->get();

            // Validate template weights sum to 100
            $totalWeight = $templateTasks->sum('weight');
            if ($totalWeight !== 100) {
                DB::rollBack();
                return [
                    'success' => false,
                    'project' => null,
                    'tasks_count' => 0,
                    'message' => "Template validation failed. Task weights sum to {$totalWeight}%, must be 100%"
                ];
            }

            // Create project from opportunity
            $projectName = $opportunity->client . ' - ' . $template->name . ' (' . date('Y-m-d H:i') . ')';
            
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

            // Create tasks from template
            foreach ($templateTasks as $templateTask) {
                DB::table('tasks')->insertGetId([
                    'project_id' => $projectId,
                    'name' => $templateTask->name,
                    'description' => $templateTask->description,
                    'weight' => $templateTask->weight,
                    'phase_number' => $templateTask->phase_number,
                    'estimated_duration_days' => $templateTask->estimated_duration_days,
                    'dependencies' => $templateTask->dependencies,
                    'status' => 'planned',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $tasksCreated++;
            }

            // Log project creation in audit trail
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
                    'template_id' => $templateId,
                    'template_name' => $template->name,
                    'source' => 'template_based_creation',
                    'tasks_count' => $tasksCreated
                ],
                $request
            );

            // Commit transaction
            DB::commit();

            // Fetch complete project with tasks
            $project = DB::table('projects')
                ->where('id', $projectId)
                ->first();

            $tasks = DB::table('tasks')
                ->where('project_id', $projectId)
                ->orderBy('phase_number')
                ->get();

            return [
                'success' => true,
                'project' => (array)$project,
                'tasks' => $tasks->toArray(),
                'tasks_count' => $tasksCreated,
                'template_name' => $template->name,
                'message' => "Project created successfully with {$tasksCreated} tasks from '{$template->name}' template"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'project' => null,
                'tasks_count' => $tasksCreated,
                'message' => 'Failed to create project with template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Apply template to existing project (Phase 5.4)
     * 
     * Applies a template to an existing project that doesn't have tasks yet.
     * Useful for manual project creation where user wants to apply template later.
     * 
     * @param int $projectId The ID of the project
     * @param int $templateId The ID of the template to apply
     * @param int $userId The user who triggered the action
     * @param Request|null $request Optional request for audit trail
     * @return array ['success' => bool, 'tasks_count' => int, 'message' => string]
     */
    public function applyTemplateToProject(int $projectId, int $templateId, int $userId, ?Request $request = null): array
    {
        $tasksCreated = 0;

        try {
            DB::beginTransaction();

            // Verify project exists
            $project = DB::table('projects')
                ->where('id', $projectId)
                ->first();

            if (!$project) {
                DB::rollBack();
                return [
                    'success' => false,
                    'tasks_count' => 0,
                    'message' => 'Project not found'
                ];
            }

            // Check if project already has tasks
            $existingTasks = DB::table('tasks')
                ->where('project_id', $projectId)
                ->count();

            if ($existingTasks > 0) {
                DB::rollBack();
                return [
                    'success' => false,
                    'tasks_count' => 0,
                    'message' => "Cannot apply template. Project already has {$existingTasks} tasks. Delete existing tasks first."
                ];
            }

            // Verify template exists
            $template = DB::table('project_templates')
                ->where('id', $templateId)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                DB::rollBack();
                return [
                    'success' => false,
                    'tasks_count' => 0,
                    'message' => 'Template not found or is inactive'
                ];
            }

            // Get template tasks
            $templateTasks = DB::table('project_template_tasks')
                ->where('project_template_id', $templateId)
                ->orderBy('phase_number')
                ->get();

            // Create tasks from template
            foreach ($templateTasks as $templateTask) {
                DB::table('tasks')->insertGetId([
                    'project_id' => $projectId,
                    'name' => $templateTask->name,
                    'description' => $templateTask->description,
                    'weight' => $templateTask->weight,
                    'phase_number' => $templateTask->phase_number,
                    'estimated_duration_days' => $templateTask->estimated_duration_days,
                    'dependencies' => $templateTask->dependencies,
                    'status' => 'planned',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $tasksCreated++;
            }

            // Log action in audit trail
            $this->auditService->logCreate(
                $userId,
                'projects',
                $projectId,
                [
                    'template_applied' => true,
                    'template_id' => $templateId,
                    'template_name' => $template->name,
                    'tasks_created' => $tasksCreated,
                ],
                $request
            );

            DB::commit();

            return [
                'success' => true,
                'tasks_count' => $tasksCreated,
                'template_name' => $template->name,
                'message' => "{$tasksCreated} tasks applied from '{$template->name}' template"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'tasks_count' => $tasksCreated,
                'message' => 'Failed to apply template: ' . $e->getMessage()
            ];
        }
    }
}
