<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectProgressService
{
    /**
     * Calculate overall progress for a project
     *
     * Formula:
     * Project Progress = Σ(task.progress × task.weight / 100)
     *
     * @param int $projectId
     * @return float
     */
    public function calculateProjectProgress(int $projectId): float
    {
        $tasks = DB::table('tasks')
            ->where('project_id', $projectId)
            ->select('progress', 'weight')
            ->get();

        $progress = 0.0;

        foreach ($tasks as $task) {
            $taskProgress = max(0, min(100, (float) $task->progress));
            $taskWeight   = (float) $task->weight;

            $progress += ($taskProgress * $taskWeight) / 100;
        }

        return round(min(100, max(0, $progress)), 2);
    }
}
