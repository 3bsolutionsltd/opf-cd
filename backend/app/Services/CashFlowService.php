<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * CashFlowService
 * 
 * Aggregates cash flow metrics across all accounts.
 * 
 * Formulas:
 * - Cash at Hand = Opening Balance + Inflows − Outflows
 * - Cash Runway (months) = Cash at Hand / Average Monthly Burn
 * 
 * Assumptions:
 * - Monthly burn includes actual outflows + unpaid expenses
 * - Burn is averaged over last 3 months (or available data)
 * - All amounts are aggregated across accounts (no currency conversion)
 * 
 * Source: docs/_truth.md
 */
class CashFlowService
{
    /**
     * Get cash flow snapshot aggregated across all accounts
     * 
     * @return array [
     *   'cash_at_hand' => float,
     *   'total_inflows' => float,
     *   'total_outflows' => float,
     *   'net_cash_flow' => float,
     *   'average_monthly_burn' => float,
     *   'cash_runway_months' => float|null
     * ]
     */
    public function getCashFlowSnapshot(): array
    {
        // Sum opening balances from all accounts
        $totalOpeningBalance = DB::table('accounts')
            ->sum('opening_balance');

        // Sum all inflows from cash_transactions
        $totalInflows = DB::table('cash_transactions')
            ->where('type', 'inflow')
            ->sum('amount');

        // Sum all outflows from cash_transactions
        $totalOutflows = DB::table('cash_transactions')
            ->where('type', 'outflow')
            ->sum('amount');

        // Cash at Hand = Opening Balance + Inflows − Outflows
        $cashAtHand = $totalOpeningBalance + $totalInflows - $totalOutflows;

        // Net Cash Flow = Inflows - Outflows
        $netCashFlow = $totalInflows - $totalOutflows;

        // Calculate average monthly burn (outflows only, last 3 months)
        // Burn = actual outflows + unpaid expenses
        $averageMonthlyBurn = $this->calculateMonthlyBurn();

        // Calculate cash runway
        // If burn = 0, runway is infinite (return null)
        $cashRunwayMonths = null;
        if ($averageMonthlyBurn > 0) {
            $cashRunwayMonths = $cashAtHand / $averageMonthlyBurn;
        }

        return [
            'cash_at_hand' => round($cashAtHand, 2),
            'total_inflows' => round($totalInflows, 2),
            'total_outflows' => round($totalOutflows, 2),
            'net_cash_flow' => round($netCashFlow, 2),
            'average_monthly_burn' => round($averageMonthlyBurn, 2),
            'cash_runway_months' => $cashRunwayMonths !== null ? round($cashRunwayMonths, 2) : null,
        ];
    }

    /**
     * Calculate average monthly burn rate
     * 
     * Burn = actual outflows + unpaid expenses
     * Averaged over last 3 months (or available data if less than 3 months)
     * 
     * @return float
     */
    private function calculateMonthlyBurn(): float
    {
        // Get date 3 months ago
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));

        // Sum outflows from last 3 months
        $recentOutflows = DB::table('cash_transactions')
            ->where('type', 'outflow')
            ->where('transaction_date', '>=', $threeMonthsAgo)
            ->sum('amount');

        // Sum unpaid expenses (status != 'paid') due within the last 3 months
        // These represent near-term cash obligations contributing to burn
        $unpaidExpenses = DB::table('expenses')
            ->where('status', '!=', 'paid')
            ->where('due_date', '>=', $threeMonthsAgo)
            ->sum('amount');

        // Total burn over period
        $totalBurn = $recentOutflows + $unpaidExpenses;

        // Calculate number of months in period
        // Use actual date range or default to 3
        $oldestTransaction = DB::table('cash_transactions')
            ->where('type', 'outflow')
            ->where('transaction_date', '>=', $threeMonthsAgo)
            ->min('transaction_date');

        $monthsInPeriod = 3;
        if ($oldestTransaction) {
            $start = strtotime($oldestTransaction);
            $end = time();
            $monthsInPeriod = max(1, round(($end - $start) / (30 * 24 * 60 * 60)));
        }

        // Average monthly burn
        return $totalBurn / $monthsInPeriod;
    }
}
