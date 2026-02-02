<?php

namespace App\Services;

/**
 * ExpenseSchedulerService
 * 
 * Manages recurring expense generation and scheduling.
 * 
 * Rules:
 * - Recurring expenses auto-generate future instances
 * - Paid expenses cannot be edited
 * - Only generate future instances when needed (not in SQL)
 * 
 * Source: docs/_truth.md
 */
class ExpenseSchedulerService
{
    /**
     * Generate next instance of a recurring expense
     * 
     * @param int $expenseId
     * @return int|null ID of new expense or null if cannot generate
     */
    public function generateNextInstance(int $expenseId): ?int
    {
        // TODO: Implement
        // 1. Get the recurring expense
        // 2. Check if type = 'recurring'
        // 3. Calculate next due_date based on frequency (monthly, quarterly, annual)
        // 4. Create new expense record with same details but new due_date
        // 5. Return new expense ID
        
        return null;
    }

    /**
     * Generate future instances for all recurring expenses
     * 
     * @param int $monthsAhead Number of months to generate ahead
     * @return array IDs of generated expenses
     */
    public function generateFutureInstances(int $monthsAhead = 3): array
    {
        // TODO: Implement
        // 1. Get all recurring expenses
        // 2. For each, check if instances exist for next N months
        // 3. Generate missing instances
        // 4. Return array of generated expense IDs
        
        return [];
    }

    /**
     * Calculate next due date for a recurring expense
     * 
     * @param string $currentDueDate
     * @param string $frequency 'monthly', 'quarterly', or 'annual'
     * @return string
     */
    public function calculateNextDueDate(string $currentDueDate, string $frequency): string
    {
        // TODO: Implement
        // Based on frequency, calculate next due date
        // monthly: +1 month
        // quarterly: +3 months
        // annual: +12 months
        
        return $currentDueDate;
    }

    /**
     * Mark expense as paid
     * 
     * @param int $expenseId
     * @return bool
     */
    public function markAsPaid(int $expenseId): bool
    {
        // TODO: Implement
        // 1. Verify expense exists and status != 'paid'
        // 2. Update status to 'paid'
        // 3. If recurring, generate next instance
        // 4. Return success
        
        return false;
    }

    /**
     * Get upcoming expenses due within date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string|null $status Filter by status
     * @return array
     */
    public function getUpcomingExpenses(string $startDate, string $endDate, ?string $status = null): array
    {
        // TODO: Implement
        // Get expenses with due_date in range
        // Filter by status if provided
        
        return [];
    }

    /**
     * Check if expense can be edited
     * 
     * @param int $expenseId
     * @return bool
     */
    public function canEdit(int $expenseId): bool
    {
        // TODO: Implement
        // Rule: Paid expenses cannot be edited
        // Return false if status = 'paid'
        
        return true;
    }
}
