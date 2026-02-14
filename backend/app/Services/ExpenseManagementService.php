<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
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
                // Calculate overdue status: due date passed AND not yet paid
                $isOverdue = $expense->status === 'due' && Carbon::parse($expense->due_date)->lt(Carbon::now());
                
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
                    'is_overdue' => $isOverdue,
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

        // Calculate overdue status: due date passed AND not yet paid
        $isOverdue = $expense->status === 'due' && Carbon::parse($expense->due_date)->lt(Carbon::now());
        
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
            'is_overdue' => $isOverdue,
        ];
    }

    /**
     * Create a new expense.
     * 
     * @param array $data Expense data (name, category, amount, currency, type, frequency, status, project_id, due_date)
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string, 'expense_id' => int|null]
     */
    public function createExpense(array $data, int $userId, ?Request $request = null): array
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

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'expenses',
                $expenseId,
                array_merge($data, ['id' => $expenseId]),
                $request
            );

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
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateExpense(int $expenseId, array $data, int $userId, ?Request $request = null): array
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

        // Store before state for audit log
        $before = (array) $expense;

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

            // Get after state for audit log
            $after = (array) DB::table('expenses')->where('id', $expenseId)->first();

            // Log audit trail
            $this->auditService->logUpdate(
                $userId,
                'expenses',
                $expenseId,
                $before,
                $after,
                $request
            );

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
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteExpense(int $expenseId, int $userId, ?Request $request = null): array
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

        // Store final state for audit log
        $deletedData = (array) $expense;

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

            // Log audit trail
            $this->auditService->logDelete(
                $userId,
                'expenses',
                $expenseId,
                $deletedData,
                $request
            );

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
     * Overdue is calculated from 'due' expenses with past due dates.
     * 
     * @param int|null $projectId Optional project filter
     * @return array ['currencies' => ['USD' => ['due' => float, 'paid' => float, 'overdue' => float, 'total' => float]]]
     */
    public function getExpensesSummary(?int $projectId = null): array
    {
        $query = DB::table('expenses')
            ->select('status', 'currency', 'due_date', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->groupBy('status', 'currency', 'due_date');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $expenses = $query->get();
        $today = Carbon::now()->startOfDay();
        $currencies = [];

        foreach ($expenses as $expense) {
            $currency = $expense->currency;
            $status = $expense->status;
            
            if (!isset($currencies[$currency])) {
                $currencies[$currency] = [
                    'due' => 0.0,
                    'paid' => 0.0,
                    'overdue' => 0.0,
                    'total' => 0.0,
                ];
            }

            $amount = (float) $expense->total;
            
            if ($status === 'paid') {
                $currencies[$currency]['paid'] += $amount;
            } elseif ($status === 'due') {
                // Split 'due' into current and overdue based on due_date
                $dueDate = Carbon::parse($expense->due_date);
                if ($dueDate->lt($today)) {
                    $currencies[$currency]['overdue'] += $amount;
                } else {
                    $currencies[$currency]['due'] += $amount;
                }
            }
            
            $currencies[$currency]['total'] += $amount;
        }

        return [
            'currencies' => $currencies,
        ];
    }

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
     * Overdue status is now calculated dynamically in service layer based on:
     * is_overdue = (status === 'due' && due_date < current_date)
     * 
     * This follows the principle: "Database stores facts, never conclusions."
     */
}
