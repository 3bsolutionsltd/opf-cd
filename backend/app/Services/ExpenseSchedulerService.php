<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use DateTime;
use DateInterval;

/**
 * ExpenseSchedulerService
 * 
 * Projects upcoming expense occurrences for the next 90 days.
 * 
 * Rules:
 * - Includes all unpaid expenses (status != 'paid')
 * - Projects recurring expenses based on frequency (monthly, quarterly, annual)
 * - All projections are calculated in-memory (no database writes)
 * - Projection window: next 90 days from today
 * 
 * Source: docs/_truth.md
 */
class ExpenseSchedulerService
{
    /**
     * Get upcoming expenses with projected recurring occurrences
     * 
     * Returns original unpaid expenses plus projected future occurrences of recurring expenses,
     * all within a 90-day window from today.
     * 
     * @return array [
     *   [
     *     'expense_id' => int,
     *     'name' => string,
     *     'category' => string,
     *     'amount' => float,
     *     'currency' => string,
     *     'due_date' => string,
     *     'type' => string (one_off|recurring),
     *     'source' => string (original|projected)
     *   ],
     *   ...
     * ]
     */
    public function getUpcomingExpenses(): array
    {
        $today = new DateTime();
        $windowEnd = (clone $today)->add(new DateInterval('P90D')); // 90 days from now

        // Get all unpaid expenses
        $expenses = DB::table('expenses')
            ->where('status', '!=', 'paid')
            ->select('id', 'name', 'category', 'amount', 'currency', 'due_date', 'type', 'frequency')
            ->get();

        $upcomingExpenses = [];

        foreach ($expenses as $expense) {
            $dueDate = new DateTime($expense->due_date);

            // Include original expense if within 90-day window
            if ($dueDate >= $today && $dueDate <= $windowEnd) {
                $upcomingExpenses[] = [
                    'expense_id' => $expense->id,
                    'name' => $expense->name,
                    'category' => $expense->category,
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'due_date' => $expense->due_date,
                    'type' => $expense->type,
                    'source' => 'original',
                ];
            }

            // For recurring expenses, project future occurrences
            if ($expense->type === 'recurring' && $expense->frequency !== null) {
                // Calculate interval based on frequency
                $interval = null;
                switch ($expense->frequency) {
                    case 'monthly':
                        $interval = new DateInterval('P1M'); // 1 month
                        break;
                    case 'quarterly':
                        $interval = new DateInterval('P3M'); // 3 months
                        break;
                    case 'annual':
                        $interval = new DateInterval('P1Y'); // 1 year
                        break;
                }

                if ($interval !== null) {
                    // Start from original due_date and generate future occurrences
                    $currentDate = clone $dueDate;
                    
                    // Generate occurrences until we exceed the 90-day window
                    while (true) {
                        $currentDate->add($interval);
                        
                        // Stop if beyond window
                        if ($currentDate > $windowEnd) {
                            break;
                        }

                        // Only include if >= today
                        if ($currentDate >= $today) {
                            $upcomingExpenses[] = [
                                'expense_id' => $expense->id,
                                'name' => $expense->name,
                                'category' => $expense->category,
                                'amount' => (float) $expense->amount,
                                'currency' => $expense->currency,
                                'due_date' => $currentDate->format('Y-m-d'),
                                'type' => $expense->type,
                                'source' => 'projected',
                            ];
                        }
                    }
                }
            }
        }

        // Sort by due_date ascending
        usort($upcomingExpenses, function ($a, $b) {
            return strcmp($a['due_date'], $b['due_date']);
        });

        return $upcomingExpenses;
    }
}
