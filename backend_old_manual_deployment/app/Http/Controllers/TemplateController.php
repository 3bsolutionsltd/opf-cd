<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TemplateManagementService;

/**
 * TemplateController
 * 
 * Thin pass-through controller following OPF-CD strict rules.
 * Injects ONLY ONE business service (TemplateManagementService).
 * Contains NO business logic, NO calculations, NO transformations.
 * 
 * Public endpoints (Project Managers):
 * - GET /api/templates - List active templates
 * - GET /api/templates/{id} - Get template with tasks
 * - GET /api/templates/{id}/preview - Preview template tasks
 * 
 * Admin endpoints:
 * - POST /api/admin/templates - Create template
 * - PUT /api/admin/templates/{id} - Update template
 * - DELETE /api/admin/templates/{id} - Delete template
 * - POST /api/admin/templates/{id}/tasks - Add task to template
 * - PUT /api/admin/templates/tasks/{taskId} - Update task
 * - DELETE /api/admin/templates/tasks/{taskId} - Delete task
 */
class TemplateController extends Controller
{
    private TemplateManagementService $service;

    public function __construct(TemplateManagementService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/templates
     * List all active templates
     */
    public function index(): JsonResponse
    {
        try {
            $templates = $this->service->getAllActiveTemplates();
            
            return response()->json([
                'success' => true,
                'data' => $templates,
                'count' => count($templates),
                'message' => 'Templates retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/templates/{id}
     * Get template with all tasks
     */
    public function show(int $id): JsonResponse
    {
        try {
            $template = $this->service->getTemplateWithTasks($id);
            
            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $template,
                'message' => 'Template retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/templates/{id}/preview
     * Preview template tasks without applying
     * 
     * All calculations delegated to service layer (compliant with rules).
     */
    public function preview(int $id): JsonResponse
    {
        try {
            $result = $this->service->getTemplatePreview($id);
            
            if (!$result['success']) {
                return response()->json($result, 404);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/opportunities/{opportunityId}/projects/with-template
     * Create project from opportunity with template
     * 
     * Uses middleware authentication pattern (compliant with rules).
     */
    public function createProjectWithTemplate(Request $request, int $opportunityId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:project_templates,id'
            ]);

            $userId = $request->get('authenticated_user_id');

            $result = $this->service->createProjectWithTemplate(
                $opportunityId,
                $validated['template_id'],
                $userId,
                $request
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $result['project'],
                    'tasks' => $result['tasks'] ?? [],
                    'tasks_count' => $result['tasks_count'],
                    'template_name' => $result['template_name']
                ],
                'message' => $result['message']
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/projects/{projectId}/apply-template
     * Apply template to existing project
     * 
     * Uses middleware authentication pattern (compliant with rules).
     */
    public function applyTemplate(Request $request, int $projectId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:project_templates,id'
            ]);

            $userId = $request->get('authenticated_user_id');

            $result = $this->service->applyTemplateToProject(
                $projectId,
                $validated['template_id'],
                $userId,
                $request
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks_count' => $result['tasks_count'],
                    'template_name' => $result['template_name']
                ],
                'message' => $result['message']
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply template: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========== ADMIN ENDPOINTS ==========

    /**
     * GET /api/admin/templates
     * List all templates (including inactive) - Admin only
     */
    public function adminIndex(): JsonResponse
    {
        try {
            $templates = $this->service->getAllTemplates();
            
            return response()->json([
                'success' => true,
                'data' => $templates,
                'count' => count($templates),
                'message' => 'All templates retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/admin/templates
     * Create new template - Admin only
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:project_templates',
                'description' => 'nullable|string',
                'category' => 'required|string',
                'is_active' => 'boolean',
                'average_duration_days' => 'nullable|integer|min:1'
            ]);

            $templateId = $this->service->createTemplate($validated);

            return response()->json([
                'success' => true,
                'data' => ['id' => $templateId],
                'message' => 'Template created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/templates/{id}
     * Update template - Admin only
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'string|unique:project_templates,name,' . $id,
                'description' => 'nullable|string',
                'category' => 'string',
                'is_active' => 'boolean',
                'average_duration_days' => 'nullable|integer|min:1'
            ]);

            $success = $this->service->updateTemplate($id, $validated);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/templates/{id}
     * Delete template - Admin only
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->service->deleteTemplate($id);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * POST /api/admin/templates/{id}/tasks
     * Add task to template - Admin only
     */
    public function addTask(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'weight' => 'required|integer|min:0|max:100',
                'phase_number' => 'required|integer|min:1',
                'estimated_duration_days' => 'nullable|integer|min:1',
                'dependencies' => 'nullable|string'
            ]);

            $taskId = $this->service->addTaskToTemplate($id, $validated);

            return response()->json([
                'success' => true,
                'data' => ['id' => $taskId],
                'message' => 'Task added to template successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/admin/templates/tasks/{taskId}
     * Update template task - Admin only
     */
    public function updateTask(Request $request, int $taskId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'string',
                'description' => 'nullable|string',
                'weight' => 'integer|min:0|max:100',
                'phase_number' => 'integer|min:1',
                'estimated_duration_days' => 'nullable|integer|min:1',
                'dependencies' => 'nullable|string'
            ]);

            $success = $this->service->updateTemplateTask($taskId, $validated);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/templates/tasks/{taskId}
     * Delete template task - Admin only
     */
    public function deleteTask(int $taskId): JsonResponse
    {
        try {
            $success = $this->service->deleteTemplateTask($taskId);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ], 500);
        }
    }
}
