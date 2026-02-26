<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Http\Requests\CreateProjectFromOpportunityRequest;
use App\Services\OpportunityManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

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

    /**
     * Constructor - inject ONE service only
     */
    public function __construct(OpportunityManagementService $opportunityService)
    {
        $this->opportunityService = $opportunityService;
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
}
