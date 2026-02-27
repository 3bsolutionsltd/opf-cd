<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Http\Requests\CreateProjectFromOpportunityRequest;
use App\Services\OpportunityManagementService;
use App\Services\ProjectTemplateService;
use App\Services\OpportunityProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

/**
 * Opportunity Controller
 * 
 * Thin pass-through controller - NO transformations, NO calculations.
 * Calls ONE service (OpportunityManagementService).
 * 
 * User ID is injected by InjectAuthenticatedUserId middleware.
 */
class OpportunityController extends Controller
{
    /**
     * Injected service
     */
    private OpportunityManagementService $opportunityService;
    private ProjectTemplateService $templateService;
    private OpportunityProjectService $projectService;

    /**
     * Constructor - inject services
     */
    public function __construct(
        OpportunityManagementService $opportunityService,
        ProjectTemplateService $templateService,
        OpportunityProjectService $projectService
    ) {
        $this->opportunityService = $opportunityService;
        $this->templateService = $templateService;
        $this->projectService = $projectService;
    }

    /**
     * Display opportunities list view.
     */
    public function index(): View
    {
        return view('opportunities.index');
    }

    /**
     * Display opportunity creation form.
     */
    public function create(): View
    {
        return view('opportunities.create');
    }

    /**
     * Store a new opportunity.
     */
    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->opportunityService->createOpportunity(
            $request->validated(),
            $userId,
            $request
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * Display opportunity edit form.
     */
    public function edit(int $opportunityId): View
    {
        return view('opportunities.edit', ['opportunityId' => $opportunityId]);
    }

    /**
     * Display projects for an opportunity.
     */
    public function showProjects(int $opportunityId): View
    {
        return view('opportunities.projects', ['opportunityId' => $opportunityId]);
    }

    /**
     * Update an existing opportunity.
     */
    public function update(UpdateOpportunityRequest $request, int $opportunityId): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->opportunityService->updateOpportunity(
            $opportunityId,
            $request->validated(),
            $userId,
            $request
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Delete an opportunity.
     */
    public function destroy(\Illuminate\Http\Request $request, int $opportunityId): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->opportunityService->deleteOpportunity($opportunityId, $userId, $request);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * API: Get all opportunities.
     */
    public function apiIndex(): JsonResponse
    {
        $opportunities = $this->opportunityService->getOpportunities();
        return response()->json($opportunities, 200);
    }

    /**
     * API: Get opportunity details by ID.
     */
    public function apiShow(int $opportunityId): JsonResponse
    {
        $opportunity = $this->opportunityService->getOpportunityDetails($opportunityId);

        if (!$opportunity) {
            return response()->json([
                'success' => false,
                'message' => 'Opportunity not found.',
            ], 404);
        }

        return response()->json($opportunity, 200);
    }

    /**
     * API: Create a project from an opportunity (manual creation).
     * 
     * Supports multi-phase opportunities where multiple projects
     * can be created from a single opportunity.
     */
    public function createProject(CreateProjectFromOpportunityRequest $request, int $opportunityId): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->opportunityService->createProjectFromOpportunity(
            $opportunityId,
            $request->validated(),
            $userId,
            $request
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * API: Get all projects linked to an opportunity.
     */
    public function getProjects(int $opportunityId): JsonResponse
    {
        $projects = $this->opportunityService->getProjectsForOpportunity($opportunityId);
        
        return response()->json([
            'success' => true,
            'projects' => $projects
        ], 200);
    }

    /**
     * Show template selection form for creating project from opportunity.
     * Phase 5.4 - Frontend Integration
     */
    public function showTemplateSelection(int $opportunityId): View
    {
        // Fetch the opportunity
        $opportunity = $this->opportunityService->getOpportunityDetails($opportunityId);

        if (!$opportunity) {
            abort(404, 'Opportunity not found');
        }

        // Get all active templates
        $templates = $this->templateService->getAllActiveTemplates();

        return view('opportunities.templates.create-project-with-template', [
            'opportunity' => $opportunity,
            'templates' => $templates
        ]);
    }

    /**
     * Create project with template using form submission.
     * Handles both API and form submission requests.
     * Phase 5.4 - Frontend Integration
     */
    public function createProjectWithTemplate(Request $request, int $opportunityId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:project_templates,id'
            ]);

            $userId = $request->get('authenticated_user_id') ?? auth()->id() ?? 1;

            // Call service to create project with template
            $result = $this->projectService->createProjectWithTemplate(
                $opportunityId,
                $validated['template_id'],
                $userId,
                $request
            );

            if ($result['success']) {
                return response()->json($result, 201);
            }

            return response()->json($result, 400);
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
     * Show form for applying template to existing project.
     * Phase 5.4 - Frontend Integration
     */
    public function showApplyTemplate(int $projectId): View
    {
        $templates = $this->templateService->getAllActiveTemplates();

        return view('opportunities.templates.apply-template', [
            'projectId' => $projectId,
            'templates' => $templates
        ]);
    }
}
