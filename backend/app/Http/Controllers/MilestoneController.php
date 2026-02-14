<?php

namespace App\Http\Controllers;

use App\Services\MilestoneManagementService;
use App\Services\ReceiveProjectPaymentService;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Http\Requests\RecordPaymentRequest;
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
    private ReceiveProjectPaymentService $paymentService;

    public function __construct(
        MilestoneManagementService $milestoneService,
        ReceiveProjectPaymentService $paymentService
    ) {
        $this->milestoneService = $milestoneService;
        $this->paymentService = $paymentService;
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
        $userId = $request->user()->id;
        $result = $this->milestoneService->createMilestone(
            $projectId,
            $request->validated(),
            $userId,
            $request
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
        $userId = $request->user()->id;
        $result = $this->milestoneService->updateMilestone(
            $milestoneId,
            $request->validated(),
            $userId,
            $request
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Delete a milestone.
     */
    public function destroy(\Illuminate\Http\Request $request, int $milestoneId): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->milestoneService->deleteMilestone($milestoneId, $userId, $request);

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

    /**
     * Record receipt of a project payment.
     * 
     * This is the ONLY way to mark a milestone as paid.
     * Atomically creates cash_transaction and updates milestone status.
     * 
     * Required fields:
     * - account_id: Which account received the funds
     * - transaction_date: When payment was received (YYYY-MM-DD)
     */
    public function recordPayment(RecordPaymentRequest $request, int $milestoneId): JsonResponse
    {
        $result = $this->paymentService->receive(
            $milestoneId,
            $request->validated()['account_id'],
            $request->validated()['transaction_date']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
