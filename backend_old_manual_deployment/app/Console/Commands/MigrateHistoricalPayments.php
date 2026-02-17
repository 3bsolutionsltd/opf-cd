<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate Historical Payment Data
 * 
 * Purpose: Create cash_transactions records for payment_milestones 
 * that were marked as 'paid' before ReceiveProjectPaymentService was implemented.
 * 
 * This command is idempotent and safe to run multiple times.
 * 
 * Usage:
 *   php artisan payments:migrate-historical [--dry-run]
 */
class MigrateHistoricalPayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:migrate-historical
                            {--dry-run : Preview changes without executing them}
                            {--account-id= : Default account ID to use for historical transactions}
                            {--milestone-ids=* : Specific milestone IDs to migrate (space-separated)}
                            {--currency= : Filter milestones by currency (USD, UGX)}
                            {--interactive : Confirm each milestone individually}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate historical paid milestones to create corresponding cash_transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $defaultAccountId = $this->option('account-id');
        $milestoneIds = $this->option('milestone-ids');
        $currencyFilter = $this->option('currency');
        $isInteractive = $this->option('interactive');
        
        $this->info('=== Historical Payment Migration ===');
        $this->newLine();
        
        // Step 1: Find orphaned paid milestones
        $orphanedMilestones = $this->findOrphanedMilestones($milestoneIds, $currencyFilter);
        
        if ($orphanedMilestones->isEmpty()) {
            $this->info('✓ No orphaned payments found. All paid milestones have corresponding transactions.');
            return 0;
        }
        
        $this->warn("Found {$orphanedMilestones->count()} paid milestone(s) without cash_transactions:");
        $this->newLine();
        
        // Display table of orphaned milestones
        $this->table(
            ['ID', 'Project', 'Milestone', 'Amount', 'Currency', 'Status'],
            $orphanedMilestones->map(fn($m) => [
                $m->id,
                $m->project_name,
                $m->milestone_name,
                number_format($m->amount, 2),
                $m->currency,
                $m->status
            ])
        );
        
        // Step 2: Process in interactive mode or batch mode
        if ($isInteractive) {
            return $this->handleInteractiveMode($orphanedMilestones, $isDryRun);
        } else {
            return $this->handleBatchMode($orphanedMilestones, $defaultAccountId, $isDryRun);
        }
    }
    
    /**
     * Handle interactive mode - confirm each milestone individually
     */
    private function handleInteractiveMode($milestones, bool $isDryRun)
    {
        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        foreach ($milestones as $milestone) {
            $this->newLine();
            $this->info("Milestone #{$milestone->id}: {$milestone->milestone_name}");
            $this->line("Project: {$milestone->project_name}");
            $this->line("Amount: {$milestone->currency} " . number_format($milestone->amount, 2));
            
            if (!$this->confirm('Migrate this milestone?', false)) {
                $this->line('Skipped.');
                $skippedCount++;
                continue;
            }
            
            // Select account for this specific milestone
            $accountId = $this->selectAccountForCurrency($milestone->currency);
            if (!$accountId) {
                $this->error('No account selected. Skipping.');
                $skippedCount++;
                continue;
            }
            
            try {
                if ($isDryRun) {
                    $this->line("Would create transaction for milestone #{$milestone->id} → Account #{$accountId}");
                    $successCount++;
                } else {
                    $this->createHistoricalTransaction($milestone, $accountId);
                    $this->info("✓ Created transaction for milestone #{$milestone->id}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Failed: {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        $this->printSummary($successCount, $skippedCount, $errorCount, $isDryRun);
        return $errorCount > 0 ? 1 : 0;
    }
    
    /**
     * Handle batch mode - migrate all with same account
     */
    private function handleBatchMode($milestones, $defaultAccountId, bool $isDryRun)
    {
        // Determine account to use
        if (!$defaultAccountId) {
            $defaultAccountId = $this->selectDefaultAccount();
            if (!$defaultAccountId) {
                $this->error('Migration cancelled. No account selected.');
                return 1;
            }
        }
        
        // Confirm migration
        if (!$isDryRun) {
            if (!$this->confirm('Create cash_transactions for these milestones?')) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }
        
        // Process each milestone
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($milestones as $milestone) {
            try {
                if ($isDryRun) {
                    $this->line("Would create transaction for milestone #{$milestone->id}");
                    $successCount++;
                } else {
                    $this->createHistoricalTransaction($milestone, $defaultAccountId);
                    $this->info("✓ Created transaction for milestone #{$milestone->id}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Failed for milestone #{$milestone->id}: {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        $this->printSummary($successCount, 0, $errorCount, $isDryRun);
        return $errorCount > 0 ? 1 : 0;
    }
    
    /**
     * Print migration summary
     */
    private function printSummary(int $successCount, int $skippedCount, int $errorCount, bool $isDryRun)
    {
        $this->newLine();
        if ($isDryRun) {
            $this->info("=== DRY RUN SUMMARY ===");
            $this->info("Would process: {$successCount} milestone(s)");
            if ($skippedCount > 0) {
                $this->line("Would skip: {$skippedCount} milestone(s)");
            }
        } else {
            $this->info("=== MIGRATION COMPLETE ===");
            $this->info("✓ Successful: {$successCount}");
            if ($skippedCount > 0) {
                $this->line("⊘ Skipped: {$skippedCount}");
            }
            if ($errorCount > 0) {
                $this->error("✗ Failed: {$errorCount}");
            }
        }
    }
    
    /**
     * Find payment milestones marked as 'paid' without corresponding cash_transactions
     */
    private function findOrphanedMilestones(?array $milestoneIds = null, ?string $currencyFilter = null)
    {
        $query = DB::table('payment_milestones as pm')
            ->leftJoin('cash_transactions as ct', function($join) {
                $join->on('ct.source_id', '=', 'pm.id')
                     ->where('ct.source_type', '=', 'payment_milestone');
            })
            ->join('projects as p', 'p.id', '=', 'pm.project_id')
            ->select(
                'pm.id',
                'pm.project_id',
                'pm.name as milestone_name',
                'pm.amount',
                'pm.currency',
                'pm.status',
                'pm.due_date',
                'pm.updated_at',
                'p.name as project_name',
                'p.contract_currency as project_currency'
            )
            ->where('pm.status', 'paid')
            ->whereNull('ct.id');
        
        // Filter by specific milestone IDs if provided
        if ($milestoneIds && count($milestoneIds) > 0) {
            $query->whereIn('pm.id', $milestoneIds);
        }
        
        // Filter by currency if provided
        if ($currencyFilter) {
            $query->where('pm.currency', strtoupper($currencyFilter));
        }
        
        return $query->orderBy('pm.updated_at')->get();
    }
    
    /**
     * Select account filtered by currency
     */
    private function selectAccountForCurrency(string $currency): ?int
    {
        $accounts = DB::table('accounts')
            ->select('id', 'name', 'type', 'currency')
            ->where('currency', $currency)
            ->orderBy('name')
            ->get();
        
        if ($accounts->isEmpty()) {
            $this->error("No {$currency} accounts found in database.");
            return null;
        }
        
        $this->newLine();
        $this->info("Select {$currency} account:");
        $this->table(
            ['ID', 'Account Name', 'Type', 'Currency'],
            $accounts->map(fn($a) => [$a->id, $a->name, $a->type, $a->currency])
        );
        
        $accountId = $this->ask('Enter account ID (or press Enter to skip)');
        
        if (empty($accountId)) {
            return null;
        }
        
        $account = $accounts->firstWhere('id', $accountId);
        if (!$account) {
            $this->error("Invalid account ID: {$accountId}");
            return null;
        }
        
        return (int) $accountId;
    }
    
    /**
     * Prompt user to select default account for historical transactions
     */
    private function selectDefaultAccount(): ?int
    {
        $accounts = DB::table('accounts')
            ->select('id', 'name', 'type', 'currency')
            ->orderBy('name')
            ->get();
        
        if ($accounts->isEmpty()) {
            $this->error('No accounts found in database. Create an account first.');
            return null;
        }
        
        $this->newLine();
        $this->info('Select account for historical transactions:');
        $this->table(
            ['ID', 'Account Name', 'Type', 'Currency'],
            $accounts->map(fn($a) => [$a->id, $a->name, $a->type, $a->currency])
        );
        
        $accountId = $this->ask('Enter account ID');
        
        $account = $accounts->firstWhere('id', $accountId);
        if (!$account) {
            $this->error("Invalid account ID: {$accountId}");
            return null;
        }
        
        return (int) $accountId;
    }
    
    /**
     * Create cash_transaction record for historical payment
     */
    private function createHistoricalTransaction($milestone, int $accountId)
    {
        // Validate account currency matches milestone currency
        $account = DB::table('accounts')->find($accountId);
        
        if ($account->currency !== $milestone->currency) {
            throw new \RuntimeException(
                "Currency mismatch: Account is {$account->currency}, milestone is {$milestone->currency}"
            );
        }
        
        DB::beginTransaction();
        
        try {
            // Create cash_transaction
            $transactionId = DB::table('cash_transactions')->insertGetId([
                'account_id' => $accountId,
                'type' => 'inflow',
                'amount' => $milestone->amount,
                'currency' => $milestone->currency,
                'source_type' => 'payment_milestone',
                'source_id' => $milestone->id,
                'transaction_date' => $milestone->updated_at ?? $milestone->due_date,
                'created_at' => now(),
            ]);
            
            // Verify milestone is still marked as paid
            $currentMilestone = DB::table('payment_milestones')->find($milestone->id);
            if ($currentMilestone->status !== 'paid') {
                throw new \RuntimeException(
                    "Milestone status changed during migration (expected 'paid', got '{$currentMilestone->status}')"
                );
            }
            
            DB::commit();
            
            return $transactionId;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
