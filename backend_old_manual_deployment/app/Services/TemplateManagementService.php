<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * TemplateManagementService
 * 
 * Orchestration service for template operations.
 * 
 * Single Responsibility: Coordinate template and project operations
 * 
 * This service orchestrates between ProjectTemplateService and OpportunityProjectService,
 * handling all business logic and calculations that were incorrectly placed in the controller.
 * 
 * Following OPF-CD patterns:
 * - Services can inject multiple OTHER services for reading calculated facts
 * - Services contain all calculations and business logic
 * - Services return structured arrays with success/failure status
 * - Controllers inject only ONE business/orchestration service
 */
class TemplateManagementService
{
    private ProjectTemplateService $templateService;
    private OpportunityProjectService $projectService;

    public function __construct(
        ProjectTemplateService $templateService,
        OpportunityProjectService $projectService
    ) {
        $this->templateService = $templateService;
        $this->projectService = $projectService;
    }

    /**
     * Get all active templates
     * Returns facts only (template data)
     */
    public function getAllActiveTemplates(): Collection
    {
        return $this->templateService->getAllActiveTemplates();
    }

    /**
     * Get all templates (including inactive)
     * Admin only
     */
    public function getAllTemplates(): Collection
    {
        return $this->templateService->getAllTemplates();
    }

    /**
     * Get template with all tasks
     * Returns complete template specification
     */
    public function getTemplateWithTasks(int $templateId): ?array
    {
        return $this->templateService->getTemplateWithTasks($templateId);
    }

    /**
     * Get template preview with validation
     * 
     * This method performs the calculation that was incorrectly in the controller.
     * Calculates total weight and validates template completeness.
     * 
     * @param int $templateId
     * @return array ['success' => bool, 'data' => array|null, 'message' => string]
     */
    public function getTemplatePreview(int $templateId): array
    {
        $template = $this->templateService->getTemplate($templateId);
        
        if (!$template) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Template not found'
            ];
        }

        $tasks = $this->templateService->getTemplateTasks($templateId);
        
        // Calculation belongs in service, not controller
        $totalWeight = $tasks->sum('weight');
        $isValid = $totalWeight === 100;

        return [
            'success' => true,
            'data' => [
                'template' => $template,
                'tasks' => $tasks,
                'total_weight' => $totalWeight,
                'is_valid' => $isValid
            ],
            'message' => 'Template preview retrieved'
        ];
    }

    /**
     * Create project from opportunity using template
     * 
     * Orchestrates template validation + project creation with template tasks.
     * 
     * @param int $opportunityId
     * @param int $templateId
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'project' => array|null, 'tasks' => array, 'tasks_count' => int, 'template_name' => string, 'message' => string]
     */
    public function createProjectWithTemplate(
        int $opportunityId,
        int $templateId,
        int $userId,
        ?Request $request = null
    ): array {
        // Validate template exists and is active
        $template = $this->templateService->getTemplate($templateId);
        
        if (!$template) {
            return [
                'success' => false,
                'project' => null,
                'tasks' => [],
                'tasks_count' => 0,
                'template_name' => null,
                'message' => 'Template not found'
            ];
        }

        if (!$template->is_active) {
            return [
                'success' => false,
                'project' => null,
                'tasks' => [],
                'tasks_count' => 0,
                'template_name' => $template->name,
                'message' => 'Template is inactive and cannot be used'
            ];
        }

        // Delegate to OpportunityProjectService for actual creation
        return $this->projectService->createProjectWithTemplate(
            $opportunityId,
            $templateId,
            $userId,
            $request
        );
    }

    /**
     * Apply template to existing project
     * 
     * Validates template and applies it to an existing project.
     * 
     * @param int $projectId
     * @param int $templateId
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'tasks_count' => int, 'template_name' => string, 'message' => string]
     */
    public function applyTemplateToProject(
        int $projectId,
        int $templateId,
        int $userId,
        ?Request $request = null
    ): array {
        // Validate template exists and is active
        $template = $this->templateService->getTemplate($templateId);
        
        if (!$template) {
            return [
                'success' => false,
                'tasks_count' => 0,
                'template_name' => null,
                'message' => 'Template not found'
            ];
        }

        if (!$template->is_active) {
            return [
                'success' => false,
                'tasks_count' => 0,
                'template_name' => $template->name,
                'message' => 'Template is inactive and cannot be used'
            ];
        }

        // Delegate to OpportunityProjectService
        return $this->projectService->applyTemplateToProject(
            $projectId,
            $templateId,
            $userId,
            $request
        );
    }

    // ========== ADMIN TEMPLATE MANAGEMENT ==========

    /**
     * Create new template (Admin only)
     * 
     * @param array $data Template data
     * @return int Template ID
     */
    public function createTemplate(array $data): int
    {
        return $this->templateService->createTemplate($data);
    }

    /**
     * Update template (Admin only)
     * 
     * @param int $templateId
     * @param array $data
     * @return bool Success status
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        return $this->templateService->updateTemplate($templateId, $data);
    }

    /**
     * Delete template (Admin only)
     * 
     * @param int $templateId
     * @return bool Success status
     * @throws \Exception if template is in use
     */
    public function deleteTemplate(int $templateId): bool
    {
        return $this->templateService->deleteTemplate($templateId);
    }

    /**
     * Add task to template (Admin only)
     * 
     * @param int $templateId
     * @param array $taskData
     * @return int Task ID
     */
    public function addTaskToTemplate(int $templateId, array $taskData): int
    {
        return $this->templateService->addTaskToTemplate($templateId, $taskData);
    }

    /**
     * Update template task (Admin only)
     * 
     * @param int $taskId
     * @param array $data
     * @return bool Success status
     */
    public function updateTemplateTask(int $taskId, array $data): bool
    {
        return $this->templateService->updateTemplateTask($taskId, $data);
    }

    /**
     * Delete template task (Admin only)
     * 
     * @param int $taskId
     * @return bool Success status
     */
    public function deleteTemplateTask(int $taskId): bool
    {
        return $this->templateService->deleteTemplateTask($taskId);
    }

    /**
     * Validate template weights sum to 100%
     * 
     * @param int $templateId
     * @return bool True if valid
     * @throws \Exception if validation fails
     */
    public function validateTemplateWeights(int $templateId): bool
    {
        return $this->templateService->validateTemplateWeights($templateId);
    }

    /**
     * Get template categories
     * 
     * @return array
     */
    public function getCategories(): array
    {
        return $this->templateService->getCategories();
    }

    /**
     * Check if template exists
     * 
     * @param int $templateId
     * @return bool
     */
    public function templateExists(int $templateId): bool
    {
        return $this->templateService->templateExists($templateId);
    }
}
