<?php

namespace App\Http\Controllers;

use App\Services\MilestoneManagementService;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Milestone Controller
 * 
 * Thin pass-through controller for payment milestone CRUD operations.
 * Separate from read-only dashboard controllers.
 * 
 * All business logic delegated to MilestoneManagementService.
 */
class MilestoneController extends Controller
{
    private MilestoneManagementService $milestoneService;

    public function __construct(MilestoneManagementService $milestoneService)
    {
        $this->milestoneService = $milestoneService;
    }

    /**
     * Display milestones list for a project.
     */
    public function index(int $projectId): View
    {
        return view('milestones.index', [
            'projectId' => $projectId,
        ]);
    }

    /**
     * Show the form for creating a new milestone.
     */
    public function create(int $projectId): View
    {
        return view('milestones.create', [
            'projectId' => $projectId,
        ]);
    }

    /**
     * Store a newly created milestone.
     */
    public function store(StoreMilestoneRequest $request, int $projectId): JsonResponse
    {
        $result = $this->milestoneService->createMilestone(
            $projectId,
            $request->validated()
        );

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Show the form for editing a milestone.
     */
    public function edit(int $milestoneId): View
    {
        $milestone = $this->milestoneService->getMilestoneDetails($milestoneId);

        if (!$milestone) {
            abort(404, 'Milestone not found.');
        }

        return view('milestones.edit', [
            'milestoneId' => $milestoneId,
            'milestone' => $milestone,
        ]);
    }

    /**
     * Update an existing milestone.
     */
    public function update(UpdateMilestoneRequest $request, int $milestoneId): JsonResponse
    {
        $result = $this->milestoneService->updateMilestone(
            $milestoneId,
            $request->validated()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Delete a milestone.
     */
    public function destroy(int $milestoneId): JsonResponse
    {
        $result = $this->milestoneService->deleteMilestone($milestoneId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * API: Get milestones for a project.
     */
    public function apiIndex(int $projectId): JsonResponse
    {
        $milestones = $this->milestoneService->getMilestonesByProject($projectId);

        return response()->json([
            'success' => true,
            'milestones' => $milestones,
        ]);
    }

    /**
     * API: Get milestone details.
     */
    public function apiShow(int $milestoneId): JsonResponse
    {
        $milestone = $this->milestoneService->getMilestoneDetails($milestoneId);

        if (!$milestone) {
            return response()->json([
                'success' => false,
                'message' => 'Milestone not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'milestone' => $milestone,
        ]);
    }

    /**
     * API: Get milestones summary (amounts by status).
     */
    public function getMilestonesSummary(int $projectId): JsonResponse
    {
        $summary = $this->milestoneService->getMilestonesSummary($projectId);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
