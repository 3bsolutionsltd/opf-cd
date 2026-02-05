<?php

namespace App\Http\Controllers;

use App\Services\TaskManagementService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    protected $taskManagementService;

    public function __construct(TaskManagementService $taskManagementService)
    {
        $this->taskManagementService = $taskManagementService;
    }

    /**
     * Display list of all tasks for a project.
     */
    public function index(int $projectId)
    {
        return view('tasks.index', ['projectId' => $projectId]);
    }

    /**
     * Show form for creating new task.
     */
    public function create(int $projectId)
    {
        return view('tasks.create', ['projectId' => $projectId]);
    }

    /**
     * Store new task (API endpoint).
     * 
     * Thin pass-through to TaskManagementService->createTask().
     */
    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $result = $this->taskManagementService->createTask($projectId, $request->validated());
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Display task details.
     */
    public function show(int $taskId)
    {
        return view('tasks.show', ['taskId' => $taskId]);
    }

    /**
     * Show form for editing task.
     */
    public function edit(int $taskId)
    {
        return view('tasks.edit', ['taskId' => $taskId]);
    }

    /**
     * Update task (API endpoint).
     * 
     * Thin pass-through to TaskManagementService->updateTask().
     */
    public function update(UpdateTaskRequest $request, int $taskId): JsonResponse
    {
        $result = $this->taskManagementService->updateTask($taskId, $request->validated());
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete task (API endpoint).
     * 
     * Thin pass-through to TaskManagementService->deleteTask().
     */
    public function destroy(int $taskId): JsonResponse
    {
        $result = $this->taskManagementService->deleteTask($taskId);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get all tasks for a project (API endpoint).
     * 
     * Thin pass-through to TaskManagementService->getTasksByProject().
     */
    public function apiIndex(int $projectId): JsonResponse
    {
        $tasks = $this->taskManagementService->getTasksByProject($projectId);
        
        return response()->json($tasks);
    }

    /**
     * Get task details (API endpoint).
     * 
     * Thin pass-through to TaskManagementService->getTaskDetails().
     */
    public function apiShow(int $taskId): JsonResponse
    {
        $task = $this->taskManagementService->getTaskDetails($taskId);
        
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        return response()->json($task);
    }

    /**
     * Get current weight sum for project (API endpoint).
     * 
     * Used by frontend for real-time weight validation display.
     */
    public function getWeightSum(int $projectId): JsonResponse
    {
        $weightSum = $this->taskManagementService->getWeightSum($projectId);
        
        return response()->json(['weight_sum' => $weightSum]);
    }
}
