<?php

namespace App\Http\Controllers;

use App\Services\GoalTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GoalTrackingController
 *
 * Thin pass-through controller. Injects ONLY GoalTrackingService.
 * Contains NO business logic, NO calculations, NO data transformations.
 *
 * Endpoints:
 *   GET    /api/goals
 *   POST   /api/goals
 *   GET    /api/goals/{id}
 *   PUT    /api/goals/{id}/update-progress
 *   GET    /api/goals/{id}/prescriptive-actions
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-006
 */
class GoalTrackingController extends Controller
{
    private GoalTrackingService $goalService;

    public function __construct(GoalTrackingService $goalService)
    {
        $this->goalService = $goalService;
    }

    /**
     * GET /api/goals
     * List all active business goals.
     */
    public function index(): JsonResponse
    {
        $goals = $this->goalService->getActiveGoals();
        return response()->json(['data' => $goals, 'count' => count($goals)]);
    }

    /**
     * POST /api/goals
     * Create a new business goal.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_type'     => 'required|string|max:100',
            'period'        => 'required|string|max:50',
            'target_value'  => 'required|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
        ]);

        $userId = $request->get('authenticated_user_id');
        $goal   = $this->goalService->createGoal($validated, (int) $userId);

        return response()->json(['data' => $goal], 201);
    }

    /**
     * GET /api/goals/{id}
     * Get a single business goal by ID.
     */
    public function show(int $id): JsonResponse
    {
        $goal = $this->goalService->getGoalById($id);

        if (!$goal) {
            return response()->json(['message' => 'Goal not found'], 404);
        }

        return response()->json(['data' => $goal]);
    }

    /**
     * PUT /api/goals/{id}/update-progress
     * Recalculate and persist progress for all active goals.
     */
    public function updateProgress(): JsonResponse
    {
        $result = $this->goalService->updateGoalProgress();
        return response()->json($result);
    }

    /**
     * GET /api/goals/{id}/prescriptive-actions
     * Get prescriptive actions for a specific goal.
     */
    public function prescriptiveActions(int $id): JsonResponse
    {
        $goal = $this->goalService->getGoalById($id);

        if (!$goal) {
            return response()->json(['message' => 'Goal not found'], 404);
        }

        $actions = $this->goalService->generatePrescriptiveActions($id);
        return response()->json($actions);
    }
}
