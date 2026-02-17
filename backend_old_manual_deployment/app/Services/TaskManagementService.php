<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TaskManagementService
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Get all tasks for a project.
     * 
     * Returns array of tasks with basic details.
     * 
     * @param int $projectId
     * @return array
     */
    public function getTasksByProject(int $projectId): array
    {
        $tasks = DB::table('tasks')
            ->where('project_id', $projectId)
            ->select(
                'id',
                'project_id',
                'name',
                'category',
                'weight',
                'progress',
                'status',
                'assigned_to',
                'start_date',
                'due_date',
                'created_at',
                'updated_at'
            )
            ->orderBy('created_at', 'asc')
            ->get();

        return $tasks->toArray();
    }

    /**
     * Get task details by ID.
     * 
     * Returns task data or null if not found.
     * 
     * @param int $taskId
     * @return array|null
     */
    public function getTaskDetails(int $taskId): ?array
    {
        $task = DB::table('tasks')
            ->where('id', $taskId)
            ->first();

        if (!$task) {
            return null;
        }

        return (array) $task;
    }

    /**
     * Create new task.
     * 
     * Validates that total weight sum does not exceed 100.
     * Enforces status/progress invariant (see validateStatusProgressInvariant).
     * Returns success fact with task ID or validation error.
     * 
     * @param int $projectId
     * @param array $data
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'task_id' => int|null, 'message' => string]
     */
    public function createTask(int $projectId, array $data, int $userId, ?Request $request = null): array
    {
        // Check if project exists
        $project = DB::table('projects')->where('id', $projectId)->exists();
        if (!$project) {
            return [
                'success' => false,
                'task_id' => null,
                'message' => 'Project not found'
            ];
        }

        // Calculate current weight sum
        $currentWeightSum = DB::table('tasks')
            ->where('project_id', $projectId)
            ->sum('weight') ?? 0;

        $newWeight = $data['weight'];
        $totalWeight = $currentWeightSum + $newWeight;

        // Validate weight sum does not exceed 100
        if ($totalWeight > 100) {
            return [
                'success' => false,
                'task_id' => null,
                'message' => 'Total task weights cannot exceed 100. Current sum: ' . $currentWeightSum . ', adding: ' . $newWeight
            ];
        }

        // Prepare values with defaults
        $status = $this->normalizeStatus($data['status'] ?? 'todo');
        $progress = $data['progress'] ?? 0;

        // INVARIANT ENFORCEMENT: Validate status and progress consistency
        $validationError = $this->validateStatusProgressInvariant($status, $progress);
        if ($validationError !== null) {
            return [
                'success' => false,
                'task_id' => null,
                'message' => $validationError
            ];
        }

        try {
            $taskId = DB::table('tasks')->insertGetId([
                'project_id' => $projectId,
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'weight' => $newWeight,
                'progress' => $progress,
                'status' => $status,
                'assigned_to' => $data['assigned_to'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'tasks',
                $taskId,
                array_merge($data, ['id' => $taskId, 'project_id' => $projectId]),
                $request
            );

            return [
                'success' => true,
                'task_id' => $taskId,
                'message' => 'Task created successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'task_id' => null,
                'message' => 'Failed to create task: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing task.
     * 
     * Validates that weight changes do not cause total to exceed 100.
     * Enforces status/progress invariant (see validateStatusProgressInvariant).
     * Returns success fact or validation error.
     * 
     * @param int $taskId
     * @param array $data
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateTask(int $taskId, array $data, int $userId, ?Request $request = null): array
    {
        // Check if task exists
        $task = DB::table('tasks')->where('id', $taskId)->first();
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }

        // Store before state for audit log
        $before = (array) $task;

        // If weight is being changed, validate sum
        if (isset($data['weight']) && $data['weight'] != $task->weight) {
            $currentWeightSum = DB::table('tasks')
                ->where('project_id', $task->project_id)
                ->where('id', '!=', $taskId)
                ->sum('weight') ?? 0;

            $newWeight = $data['weight'];
            $totalWeight = $currentWeightSum + $newWeight;

            if ($totalWeight > 100) {
                return [
                    'success' => false,
                    'message' => 'Total task weights cannot exceed 100. Current sum (excluding this task): ' . $currentWeightSum . ', new weight: ' . $newWeight
                ];
            }
        }

        // INVARIANT ENFORCEMENT: Determine final status and progress values
        // If only one field is being updated, use current value for the other
        $finalStatus = isset($data['status']) ? $this->normalizeStatus($data['status']) : $task->status;
        $finalProgress = $data['progress'] ?? $task->progress;

        // Validate the combination
        $validationError = $this->validateStatusProgressInvariant($finalStatus, $finalProgress);
        if ($validationError !== null) {
            return [
                'success' => false,
                'message' => $validationError
            ];
        }

        try {
            $updateData = [
                'updated_at' => now()
            ];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['category'])) {
                $updateData['category'] = $data['category'];
            }
            if (isset($data['weight'])) {
                $updateData['weight'] = $data['weight'];
            }
            if (isset($data['progress'])) {
                $updateData['progress'] = $data['progress'];
            }
            if (isset($data['status'])) {
                $updateData['status'] = $finalStatus; // Use normalized status
            }
            if (isset($data['assigned_to'])) {
                $updateData['assigned_to'] = $data['assigned_to'];
            }
            if (isset($data['start_date'])) {
                $updateData['start_date'] = $data['start_date'];
            }
            if (isset($data['due_date'])) {
                $updateData['due_date'] = $data['due_date'];
            }

            DB::table('tasks')
                ->where('id', $taskId)
                ->update($updateData);

            // Get after state for audit log
            $after = (array) DB::table('tasks')->where('id', $taskId)->first();

            // Log audit trail
            $this->auditService->logUpdate(
                $userId,
                'tasks',
                $taskId,
                $before,
                $after,
                $request
            );

            return [
                'success' => true,
                'message' => 'Task updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update task: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete task.
     * 
     * Returns success fact or error.
     * 
     * @param int $taskId
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteTask(int $taskId, int $userId, ?Request $request = null): array
    {
        // Check if task exists
        $task = DB::table('tasks')->where('id', $taskId)->first();
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }

        // Store final state for audit log
        $deletedData = (array) $task;

        try {
            DB::table('tasks')
                ->where('id', $taskId)
                ->delete();

            // Log audit trail
            $this->auditService->logDelete(
                $userId,
                'tasks',
                $taskId,
                $deletedData,
                $request
            );

            return [
                'success' => true,
                'message' => 'Task deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete task: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get current weight sum for a project.
     * 
     * Returns total weight as float.
     * 
     * @param int $projectId
     * @return float
     */
    public function getWeightSum(int $projectId): float
    {
        return DB::table('tasks')
            ->where('project_id', $projectId)
            ->sum('weight') ?? 0;
    }

    /**
     * Normalize status value to match database enum.
     * 
     * Converts common aliases to valid enum values:
     * - "in_progress" → "wip"
     * 
     * @param string $status
     * @return string
     */
    private function normalizeStatus(string $status): string
    {
        // Convert common aliases to valid enum values
        $statusMap = [
            'in_progress' => 'wip',
            'in-progress' => 'wip',
            'inprogress' => 'wip',
        ];

        $lowerStatus = strtolower($status);
        return $statusMap[$lowerStatus] ?? $status;
    }

    /**
     * Validate status/progress invariant.
     * 
     * CRITICAL INVARIANT:
     * - status='todo'    → progress must be exactly 0
     * - status='wip'     → progress must be between 1 and 99
     * - status='blocked' → progress must be between 1 and 99
     * - status='done'    → progress must be exactly 100
     * 
     * @param string $status
     * @param float $progress
     * @return string|null Error message if invalid, null if valid
     */
    private function validateStatusProgressInvariant(string $status, float $progress): ?string
    {
        // Rule 1: status='todo' requires progress=0
        if ($status === 'todo' && $progress != 0) {
            return "Invalid task state: status='todo' requires progress=0, but progress={$progress} was provided. " .
                   "A todo task cannot have any progress.";
        }

        // Rule 2: status='wip' requires progress between 1 and 99
        if ($status === 'wip' && ($progress < 1 || $progress > 99)) {
            return "Invalid task state: status='wip' requires progress between 1 and 99, but progress={$progress} was provided. " .
                   "Use status='todo' for 0% or status='done' for 100%.";
        }

        // Rule 3: status='blocked' requires progress between 1 and 99
        if ($status === 'blocked' && ($progress < 1 || $progress > 99)) {
            return "Invalid task state: status='blocked' requires progress between 1 and 99, but progress={$progress} was provided. " .
                   "A blocked task must have some progress but cannot be complete.";
        }

        // Rule 4: status='done' requires progress=100
        if ($status === 'done' && $progress != 100) {
            return "Invalid task state: status='done' requires progress=100, but progress={$progress} was provided. " .
                   "A done task must be fully complete.";
        }

        // All rules passed
        return null;
    }
}
