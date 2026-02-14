<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Recurring Expense Generator Service
 * 
 * Single responsibility: Generate future instances of recurring expenses ONLY.
 * Does NOT handle CRUD operations - that's ExpenseManagementService's job.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class RecurringExpenseGeneratorService
{
    /**
     * Generate future expense instances for all recurring expenses.
     * 
     * Looks ahead for the specified number of months and creates instances
     * for recurring expenses based on their frequency. All new instances start with 'due' status.
     * 
     * @param int $monthsAhead Number of months to generate ahead (default 12)
     * @return array ['success' => bool, 'generated_count' => int, 'message' => string]
     */
    public function generateRecurringExpenses(int $monthsAhead = 12): array
    {
        try {
            $recurringExpenses = DB::table('expenses')
                ->where('type', 'recurring')
                ->whereNotNull('frequency')
                ->get();

            $generatedCount = 0;
            $today = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addMonths($monthsAhead)->endOfDay();

            foreach ($recurringExpenses as $baseExpense) {
                $generatedCount += $this->generateInstancesForExpense($baseExpense, $today, $endDate);
            }

            return [
                'success' => true,
                'generated_count' => $generatedCount,
                'message' => "Generated {$generatedCount} recurring expense instances.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'generated_count' => 0,
                'message' => 'Failed to generate recurring expenses: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate instances for a single recurring expense.
     * 
     * @param object $baseExpense The recurring expense template
     * @param Carbon $startDate Start date for generation
     * @param Carbon $endDate End date for generation
     * @return int Number of instances generated
     */
    private function generateInstancesForExpense(object $baseExpense, Carbon $startDate, Carbon $endDate): int
    {
        $generatedCount = 0;
        $currentDueDate = Carbon::parse($baseExpense->due_date);

        // Calculate interval based on frequency
        $interval = match($baseExpense->frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'annual' => 12,
            default => 1,
        };

        // Move to next occurrence after the base due date
        while ($currentDueDate->lt($startDate)) {
            $currentDueDate->addMonths($interval);
        }

        // Generate instances until end date
        while ($currentDueDate->lte($endDate)) {
            // Check if instance already exists for this due date
            $exists = DB::table('expenses')
                ->where('name', $baseExpense->name)
                ->where('category', $baseExpense->category)
                ->where('amount', $baseExpense->amount)
                ->where('currency', $baseExpense->currency)
                ->where('due_date', $currentDueDate->format('Y-m-d'))
                ->exists();

            if (!$exists) {
                // All new recurring expenses start as 'due'
                // Overdue status is calculated in service layer when retrieving

                DB::table('expenses')->insert([
                    'name' => $baseExpense->name,
                    'category' => $baseExpense->category,
                    'amount' => $baseExpense->amount,
                    'currency' => $baseExpense->currency,
                    'type' => 'recurring',
                    'frequency' => $baseExpense->frequency,
                    'status' => 'due',
                    'project_id' => $baseExpense->project_id,
                    'due_date' => $currentDueDate->format('Y-m-d'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $generatedCount++;
            }

            $currentDueDate->addMonths($interval);
        }

        return $generatedCount;
    }

    /**
     * Note: The updateOverdueExpenses method has been removed.
     * Overdue status is now calculated dynamically in ExpenseManagementService based on:
     * is_overdue = (status === 'due' && due_date < current_date)
     * 
     * This follows the principle: "Database stores facts, never conclusions."
     */
}
