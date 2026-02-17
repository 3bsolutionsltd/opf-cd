<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * AGGREGATE DASHBOARD SERVICE
 * 
 * Pure fact-gathering service.
 * Returns aggregate metrics across all projects.
 * 
 * RULES:
 * - No orchestration
 * - No decisions
 * - Returns raw aggregate facts only
 */
class AggregateDashboardService
{
    /**
     * Get aggregate project progress (average across all projects).
     * 
     * Returns average completion percentage.
     * 
     * @return float|null Average progress or null if no data
     */
    public function getAggregateProgress(): ?float
    {
        $projects = DB::table('projects')->get();
        
        if ($projects->isEmpty()) {
            return null;
        }
        
        $totalProgress = 0;
        $projectCount = 0;
        
        foreach ($projects as $project) {
            $tasks = DB::table('tasks')
                ->where('project_id', $project->id)
                ->select('progress', 'weight')
                ->get();
            
            if ($tasks->isEmpty()) {
                continue;
            }
            
            $projectProgress = 0.0;
            foreach ($tasks as $task) {
                $taskProgress = max(0, min(100, (float) $task->progress));
                $taskWeight   = (float) $task->weight;
                $projectProgress += ($taskProgress * $taskWeight) / 100;
            }
            
            $totalProgress += min(100, max(0, $projectProgress));
            $projectCount++;
        }
        
        return $projectCount > 0 ? round($totalProgress / $projectCount, 2) : null;
    }
    
    /**
     * Get aggregate payment gap (sum across all projects).
     * 
     * Returns gap totals grouped by currency.
     * 
     * @return array{gaps: array<string, float>, currencies: array<string>}|null
     */
    public function getAggregatePaymentGap(): ?array
    {
        $projects = DB::table('projects')->get();
        
        if ($projects->isEmpty()) {
            return null;
        }
        
        $gapsByCurrency = [];
        
        foreach ($projects as $project) {
            $currency = $project->contract_currency ?? 'UGX';
            
            // Work delivered = milestones with status 'invoiced' or 'paid'
            $workDelivered = DB::table('payment_milestones')
                ->where('project_id', $project->id)
                ->whereIn('status', ['invoiced', 'paid'])
                ->sum('amount');
            
            // Payments received = milestones with status 'paid'
            $paymentsReceived = DB::table('payment_milestones')
                ->where('project_id', $project->id)
                ->where('status', 'paid')
                ->sum('amount');
            
            $gap = $paymentsReceived - $workDelivered;
            
            if (!isset($gapsByCurrency[$currency])) {
                $gapsByCurrency[$currency] = 0;
            }
            $gapsByCurrency[$currency] += $gap;
        }
        
        return [
            'gaps' => $gapsByCurrency,
            'currencies' => array_keys($gapsByCurrency)
        ];
    }
    
    /**
     * Get aggregate health breakdown (count by status).
     * 
     * Returns count of projects by health status.
     * 
     * @return array{healthy: int, atRisk: int, critical: int}
     */
    public function getAggregateHealth(): array
    {
        $projects = DB::table('projects')->get();
        
        $healthy = 0;
        $atRisk = 0;
        $critical = 0;
        
        foreach ($projects as $project) {
            // Calculate project health based on payment gap and progress
            $workDelivered = DB::table('payment_milestones')
                ->where('project_id', $project->id)
                ->whereIn('status', ['invoiced', 'paid'])
                ->sum('amount');
            
            $paymentsReceived = DB::table('payment_milestones')
                ->where('project_id', $project->id)
                ->where('status', 'paid')
                ->sum('amount');
            
            $gap = $paymentsReceived - $workDelivered;
            
            // Calculate project progress using same logic as ProjectProgressService
            $tasks = DB::table('tasks')
                ->where('project_id', $project->id)
                ->select('progress', 'weight')
                ->get();
            
            $progress = 0.0;
            if (!$tasks->isEmpty()) {
                foreach ($tasks as $task) {
                    $taskProgress = max(0, min(100, (float) $task->progress));
                    $taskWeight   = (float) $task->weight;
                    $progress += ($taskProgress * $taskWeight) / 100;
                }
                $progress = min(100, max(0, $progress));
            }
            
            // Determine health status
            if ($gap < -50000 || $progress < 30) {
                $critical++;
            } elseif ($gap < 0 || $progress < 70) {
                $atRisk++;
            } else {
                $healthy++;
            }
        }
        
        return [
            'healthy' => $healthy,
            'atRisk' => $atRisk,
            'critical' => $critical
        ];
    }
}
