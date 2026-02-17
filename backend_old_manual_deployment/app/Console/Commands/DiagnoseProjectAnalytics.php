<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\ProjectProgressService;
use App\Services\PaymentGapService;
use App\Services\ProjectHealthService;
use App\Services\CashFlowService;

class DiagnoseProjectAnalytics extends Command
{
    protected $signature = 'analytics:diagnose {project-name?}';
    protected $description = 'Diagnose project analytics calculations';

    public function handle()
    {
        $projectName = $this->argument('project-name') ?? 'CRM';
        
        $project = DB::table('projects')
            ->where('name', 'LIKE', "%{$projectName}%")
            ->first();
        
        if (!$project) {
            $this->error("Project matching '{$projectName}' not found");
            return 1;
        }
        
        $this->info("=== PROJECT: {$project->name} ===");
        $this->info("ID: {$project->id}");
        $this->info("Contract: {$project->contract_currency} " . number_format($project->contract_value, 2));
        $this->info("Status: {$project->status}");
        $this->newLine();
        
        // TASKS
        $this->info("=== TASKS ===");
        $tasks = DB::table('tasks')
            ->where('project_id', $project->id)
            ->get(['id', 'name', 'progress', 'weight']);
        
        $weightedProgress = 0;
        $totalWeight = 0;
        
        foreach ($tasks as $task) {
            $this->line("Task {$task->id}: {$task->name}");
            $this->line("  Progress: {$task->progress}% | Weight: {$task->weight}%");
            $this->line("  Contribution: " . round(($task->progress * $task->weight / 100), 2) . "%");
            
            $weightedProgress += ($task->progress * $task->weight / 100);
            $totalWeight += $task->weight;
        }
        
        $this->newLine();
        $this->info("Total Weighted Progress: " . round($weightedProgress, 2) . "%");
        $this->info("Total Weight: {$totalWeight}%");
        $this->newLine();
        
        // PAYMENT MILESTONES
        $this->info("=== PAYMENT MILESTONES ===");
        $milestones = DB::table('payment_milestones')
            ->where('project_id', $project->id)
            ->get();
        
        $totalPaid = 0;
        $totalPending = 0;
        
        foreach ($milestones as $milestone) {
            $this->line("Milestone {$milestone->id}: {$milestone->name}");
            $this->line("  Amount: {$milestone->currency} " . number_format($milestone->amount, 2));
            $this->line("  Status: {$milestone->status}");
            
            if ($milestone->status === 'paid') {
                $totalPaid += $milestone->amount;
            } else {
                $totalPending += $milestone->amount;
            }
        }
        
        $this->newLine();
        $this->info("Total Paid: {$project->contract_currency} " . number_format($totalPaid, 2));
        $this->info("Total Pending: {$project->contract_currency} " . number_format($totalPending, 2));
        $this->newLine();
        
        // CASH TRANSACTIONS
        $this->info("=== CASH TRANSACTIONS (for this project) ===");
        $transactions = DB::table('cash_transactions as ct')
            ->join('payment_milestones as pm', function($join) {
                $join->on('ct.source_id', '=', 'pm.id')
                     ->where('ct.source_type', '=', 'payment_milestone');
            })
            ->where('pm.project_id', $project->id)
            ->where('ct.type', 'inflow')
            ->get(['ct.id', 'ct.amount', 'ct.currency', 'ct.transaction_date', 'pm.name']);
        
        $receivedValue = 0;
        
        if ($transactions->isEmpty()) {
            $this->warn("No cash transactions found for this project!");
        } else {
            foreach ($transactions as $tx) {
                $this->line("Transaction {$tx->id}: {$tx->currency} " . number_format($tx->amount, 2));
                $this->line("  Date: {$tx->transaction_date}");
                $this->line("  Milestone: {$tx->name}");
                $receivedValue += $tx->amount;
            }
        }
        
        $this->newLine();
        $this->info("Total Received (from cash_transactions): {$project->contract_currency} " . number_format($receivedValue, 2));
        $this->newLine();
        
        // CALCULATED METRICS
        $this->info("=== CALCULATED ANALYTICS ===");
        
        $progressService = new ProjectProgressService();
        $progress = $progressService->calculateProjectProgress($project->id);
        $this->info("Project Progress: {$progress}%");
        
        $earnedValue = $project->contract_value * ($progress / 100);
        $this->info("Earned Value: {$project->contract_currency} " . number_format($earnedValue, 2));
        
        $paymentGap = $earnedValue - $receivedValue;
        $paymentGapPct = ($paymentGap / $project->contract_value) * 100;
        
        $this->info("Payment Gap: {$project->contract_currency} " . number_format($paymentGap, 2));
        $this->info("Payment Gap %: " . round($paymentGapPct, 2) . "%");
        
        if ($paymentGap > 0) {
            $this->warn("→ Client OWES you (work done, payment pending)");
        } elseif ($paymentGap < 0) {
            $this->warn("→ You OWE work (payment received, work incomplete)");
        } else {
            $this->info("→ Perfectly balanced");
        }
        
        $this->newLine();
        
        // VERIFICATION
        $this->info("=== VERIFICATION ===");
        $this->info("✓ Milestone 'paid' status: {$totalPaid}");
        $this->info("✓ Cash transactions recorded: {$receivedValue}");
        
        if ($totalPaid != $receivedValue) {
            $this->error("❌ MISMATCH: Paid milestones ({$totalPaid}) ≠ Recorded transactions ({$receivedValue})");
            $this->warn("This means some paid milestones don't have corresponding cash_transactions!");
        } else {
            $this->info("✓ Data integrity: Milestones match transactions");
        }
        
        return 0;
    }
}
