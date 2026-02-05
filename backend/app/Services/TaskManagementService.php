<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TaskManagementService
{
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
     * Returns success fact with task ID or validation error.
     * 
     * @param int $projectId
     * @param array $data
     * @return array ['success' => bool, 'task_id' => int|null, 'message' => string]
     */
    public function createTask(int $projectId, array $data): array
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

        try {
            $taskId = DB::table('tasks')->insertGetId([
                'project_id' => $projectId,
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'weight' => $newWeight,
                'progress' => $data['progress'] ?? 0,
                'status' => $data['status'] ?? 'todo',
                'assigned_to' => $data['assigned_to'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

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
     * Returns success fact or validation error.
     * 
     * @param int $taskId
     * @param array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateTask(int $taskId, array $data): array
    {
        // Check if task exists
        $task = DB::table('tasks')->where('id', $taskId)->first();
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }

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
                $updateData['status'] = $data['status'];
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
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteTask(int $taskId): array
    {
        // Check if task exists
        $task = DB::table('tasks')->where('id', $taskId)->first();
        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found'
            ];
        }

        try {
            DB::table('tasks')
                ->where('id', $taskId)
                ->delete();

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
}
