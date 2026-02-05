<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Expense Management Service
 * 
 * Single responsibility: Handle ALL expense operations including CRUD, recurring generation, and status updates.
 * Paid expenses cannot be edited or deleted to maintain financial record integrity.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class ExpenseManagementService
{
    /**
     * Get all expenses ordered by due date.
     * 
     * @param int|null $projectId Optional project filter
     * @return array Array of expense records
     */
    public function getExpenses(?int $projectId = null): array
    {
        $query = DB::table('expenses')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $expenses = $query->get()
            ->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'name' => $expense->name,
                    'category' => $expense->category,
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'type' => $expense->type,
                    'frequency' => $expense->frequency,
                    'status' => $expense->status,
                    'project_id' => $expense->project_id,
                    'due_date' => $expense->due_date,
                    'created_at' => $expense->created_at,
                    'updated_at' => $expense->updated_at,
                    'is_paid' => $expense->status === 'paid',
                ];
            })
            ->toArray();

        return $expenses;
    }

    /**
     * Get expense details by ID.
     * 
     * @param int $expenseId
     * @return array|null Expense record or null if not found
     */
    public function getExpenseDetails(int $expenseId): ?array
    {
        $expense = DB::table('expenses')
            ->where('id', $expenseId)
            ->first();

        if (!$expense) {
            return null;
        }

        return [
            'id' => $expense->id,
            'name' => $expense->name,
            'category' => $expense->category,
            'amount' => (float) $expense->amount,
            'currency' => $expense->currency,
            'type' => $expense->type,
            'frequency' => $expense->frequency,
            'status' => $expense->status,
            'project_id' => $expense->project_id,
            'due_date' => $expense->due_date,
            'created_at' => $expense->created_at,
            'updated_at' => $expense->updated_at,
            'is_paid' => $expense->status === 'paid',
        ];
    }

    /**
     * Create a new expense.
     * 
     * @param array $data Expense data (name, category, amount, currency, type, frequency, status, project_id, due_date)
     * @return array ['success' => bool, 'message' => string, 'expense_id' => int|null]
     */
    public function createExpense(array $data): array
    {
        try {
            $expenseId = DB::table('expenses')->insertGetId([
                'name' => $data['name'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'type' => $data['type'],
                'frequency' => $data['frequency'] ?? null,
                'status' => $data['status'] ?? 'due',
                'project_id' => $data['project_id'] ?? null,
                'due_date' => $data['due_date'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Expense created successfully.',
                'expense_id' => $expenseId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage(),
                'expense_id' => null,
            ];
        }
    }

    /**
     * Update an existing expense.
     * 
     * Enforces immutability: Paid expenses cannot be edited.
     * 
     * @param int $expenseId
     * @param array $data Updated expense data
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateExpense(int $expenseId, array $data): array
    {
        // Check if expense exists
        $expense = DB::table('expenses')
            ->where('id', $expenseId)
            ->first();

        if (!$expense) {
            return [
                'success' => false,
                'message' => 'Expense not found.',
            ];
        }

        // Enforce immutability: paid expenses cannot be edited
        if ($expense->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Cannot edit paid expenses. Financial records are immutable.',
            ];
        }

        try {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'category' => $data['category'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'type' => $data['type'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'status' => $data['status'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'updated_at' => now(),
            ], function ($value) {
                return $value !== null;
            });

            DB::table('expenses')
                ->where('id', $expenseId)
                ->update($updateData);

            return [
                'success' => true,
                'message' => 'Expense updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an expense.
     * 
     * Enforces immutability: Paid expenses cannot be deleted.
     * 
     * @param int $expenseId
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteExpense(int $expenseId): array
    {
        // Check if expense exists
        $expense = DB::table('expenses')
            ->where('id', $expenseId)
            ->first();

        if (!$expense) {
            return [
                'success' => false,
                'message' => 'Expense not found.',
            ];
        }

        // Enforce immutability: paid expenses cannot be deleted
        if ($expense->status === 'paid') {
            return [
                'success' => false,
                'message' => 'Cannot delete paid expenses. Financial records are immutable.',
            ];
        }

        try {
            DB::table('expenses')
                ->where('id', $expenseId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Expense deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get expense summary by status and currency.
     * 
     * @param int|null $projectId Optional project filter
     * @return array ['currencies' => ['USD' => ['due' => float, 'paid' => float, 'overdue' => float, 'total' => float]]]
     */
    public function getExpensesSummary(?int $projectId = null): array
    {
        $query = DB::table('expenses')
            ->select('status', 'currency', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('status', 'currency');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $expenses = $query->get();

        $currencies = [];

        foreach ($expenses as $expense) {
            $currency = $expense->currency;
            
            if (!isset($currencies[$currency])) {
                $currencies[$currency] = [
                    'due' => 0.0,
                    'paid' => 0.0,
                    'overdue' => 0.0,
                    'total' => 0.0,
                ];
            }

            $amount = (float) $expense->total;
            $currencies[$currency][$expense->status] = $amount;
            $currencies[$currency]['total'] += $amount;
        }

        return [
            'currencies' => $currencies,
        ];
    }

    /**
     * Generate future expense instances for all recurring expenses.
     * 
     * Looks ahead for the specified number of months and creates due/overdue instances
     * for recurring expenses based on their frequency.
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
                // Determine status based on due date
                $status = $currentDueDate->lt(Carbon::now()) ? 'overdue' : 'due';

                DB::table('expenses')->insert([
                    'name' => $baseExpense->name,
                    'category' => $baseExpense->category,
                    'amount' => $baseExpense->amount,
                    'currency' => $baseExpense->currency,
                    'type' => 'recurring',
                    'frequency' => $baseExpense->frequency,
                    'status' => $status,
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
     * Update overdue status for expenses past their due date.
     * 
     * @return array ['success' => bool, 'updated_count' => int, 'message' => string]
     */
    public function updateOverdueExpenses(): array
    {
        try {
            $updatedCount = DB::table('expenses')
                ->where('status', 'due')
                ->where('due_date', '<', Carbon::now()->format('Y-m-d'))
                ->update([
                    'status' => 'overdue',
                    'updated_at' => now(),
                ]);

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "Updated {$updatedCount} expenses to overdue status.",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'updated_count' => 0,
                'message' => 'Failed to update overdue expenses: ' . $e->getMessage(),
            ];
        }
    }
}
