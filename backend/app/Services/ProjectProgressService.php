<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ProjectProgressService
 * 
 * Calculates project progress based on weighted task completion.
 * 
 * Formula:
 * Project Progress = Σ(task.progress × task.weight / 100)
 * 
 * Rules:
 * - Task weights must sum to 100
 * - Task progress is between 0 and 100
 * - Completed tasks must have progress = 100
 * - Progress is calculated, never stored
 * 
 * Source: docs/_truth.md
 */
class ProjectProgressService
{
    /**
     * Calculate overall progress for a project
     * 
     * @param int $projectId
     * @return float Progress percentage (0-100)
     */
    public function calculateProjectProgress(int $projectId): float
    {
        // TODO: Implement
        // Project Progress = Σ(task.progress × task.weight / 100)
        // 1. Get all tasks for the project
        // 2. For each task: multiply progress by (weight / 100)
        // 3. Sum all weighted progress values
        
        $tasks = $this->getTasksForProject($projectId);

        $this->validateTaskWeights($projectId);

        $progress = 0.0;

        foreach ($tasks as $task) {
            if ($task->weight === null || $task->progress === null) {
                continue;
            }

            $taskProgress = max(0, min(100, (float) $task->progress));
            $taskWeight   = (float) $task->weight;

            $progress += ($taskProgress * $taskWeight) / 100;
        }

        return round(min(100, max(0, $progress)), 2);
    }

    /**
     * Validate that task weights sum to 100 for a project
     * 
     * @param int $projectId
     * @return bool
     */
    public function validateTaskWeights(int $projectId): void
    {
        // TODO: Implement
        // Sum all task weights for the project
        // Return true if sum equals 100
        $totalWeight = $this->getTotalTaskWeight($projectId);

        if (abs($totalWeight - 100.0) > 0.0001) {
            throw new RuntimeException(
                "Invalid task weights for project {$projectId}. Expected 100, got {$totalWeight}"
            );
        }
    }

    /**
     * Get total weight sum for a project's tasks
     * 
     * @param int $projectId
     * @return float
     */
    public function getTotalTaskWeight(int $projectId): float
    {
        // TODO: Implement
        // Sum all task weights
        $tasks = $this->getTasksForProject($projectId);

        $total = 0.0;

        foreach ($tasks as $task) {
            if ($task->weight === null) {
                continue;
            }
            $total += (float) $task->weight;
        }

        return $total;
    }

    /**
     * Get progress breakdown by task
     * 
     * @param int $projectId
     * @return array
     */
    public function getProgressBreakdown(int $projectId): array
    {
        // TODO: Implement
        // Return array of tasks with their weighted contribution
        // [
        //   ['task_id' => 1, 'name' => 'Task A', 'progress' => 50, 'weight' => 30, 'weighted_progress' => 15],
        //   ['task_id' => 2, 'name' => 'Task B', 'progress' => 100, 'weight' => 70, 'weighted_progress' => 70],
        // ]
        $tasks = $this->getTasksForProject($projectId);

        $breakdown = [];

        foreach ($tasks as $task) {
            if ($task->weight === null || $task->progress === null) {
                continue;
            }

            $taskProgress = max(0, min(100, (float) $task->progress));
            $taskWeight   = (float) $task->weight;
            $weighted = ($taskProgress * $taskWeight) / 100;
            //$weighted = ((float) $task->progress * (float) $task->weight) / 100;

            $breakdown[] = [
                'task_id'           => $task->id,
                'name'              => $task->name,
                'progress'          => (float) $task->progress,
                'weight'            => (float) $task->weight,
                'weighted_progress' => round($weighted, 2),
            ];
        }

        return $breakdown;
    }
}
