<?php

namespace App\Http\Controllers;

use App\Services\ProjectManagementService;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\JsonResponse;

class ProjectManagementController extends Controller
{
    protected $projectManagementService;

    public function __construct(ProjectManagementService $projectManagementService)
    {
        $this->projectManagementService = $projectManagementService;
    }

    /**
     * Display list of all projects.
     */
    public function index()
    {
        return view('projects.index');
    }

    /**
     * Show form for creating new project.
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store new project (API endpoint).
     * 
     * Thin pass-through to ProjectManagementService->createProject().
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->projectManagementService->createProject(
            $request->validated(),
            $userId,
            $request
        );
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Display project details.
     */
    public function show(int $id)
    {
        return view('projects.show', ['projectId' => $id]);
    }

    /**
     * Show form for editing project.
     */
    public function edit(int $id)
    {
        return view('projects.edit', ['projectId' => $id]);
    }

    /**
     * Update project (API endpoint).
     * 
     * Thin pass-through to ProjectManagementService->updateProject().
     */
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->projectManagementService->updateProject(
            $id,
            $request->validated(),
            $userId,
            $request
        );
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete project (API endpoint).
     * 
     * Thin pass-through to ProjectManagementService->deleteProject().
     */
    public function destroy(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->projectManagementService->deleteProject($id, $userId, $request);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get all projects (API endpoint).
     * 
     * Thin pass-through to ProjectManagementService->getAllProjects().
     */
    public function apiIndex(): JsonResponse
    {
        $projects = $this->projectManagementService->getAllProjects();
        
        return response()->json($projects);
    }

    /**
     * Get project details (API endpoint).
     * 
     * Thin pass-through to ProjectManagementService->getProjectDetails().
     */
    public function apiShow(int $id): JsonResponse
    {
        $project = $this->projectManagementService->getProjectDetails($id);
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }
        
        return response()->json($project);
    }

    /**
     * Check if project has received payments (API endpoint).
     * 
     * Used by frontend to conditionally make contract_value read-only.
     */
    public function hasPayments(int $id): JsonResponse
    {
        $hasPayments = $this->projectManagementService->hasReceivedPayments($id);
        
        return response()->json(['has_payments' => $hasPayments]);
    }
}
