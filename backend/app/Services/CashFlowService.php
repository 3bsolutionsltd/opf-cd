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
     * Get cash flow snapshot grouped by currency
     * 
     * @return array [
     *   'by_currency' => [
     *     'USD' => [
     *       'cash_at_hand' => float,
     *       'total_inflows' => float,
     *       'total_outflows' => float,
     *       'net_cash_flow' => float,
     *       'currency' => 'USD'
     *     ],
     *     'UGX' => [...]
     *   ],
     *   'currencies' => ['USD', 'UGX']
     * ]
     */
    public function getCashFlowSnapshot(): array
    {
        // Get all unique currencies from accounts
        $currencies = DB::table('accounts')
            ->distinct()
            ->pluck('currency')
            ->toArray();
        
        $result = [
            'by_currency' => [],
            'currencies' => $currencies
        ];
        
        foreach ($currencies as $currency) {
            $openingBalance = DB::table('accounts')
                ->where('currency', $currency)
                ->sum('opening_balance');
            
            $inflows = DB::table('cash_transactions')
                ->where('type', 'inflow')
                ->where('currency', $currency)
                ->sum('amount');
            
            $outflows = DB::table('cash_transactions')
                ->where('type', 'outflow')
                ->where('currency', $currency)
                ->sum('amount');
            
            $cashAtHand = $openingBalance + $inflows - $outflows;
            $netCashFlow = $inflows - $outflows;
            
            $result['by_currency'][$currency] = [
                'cash_at_hand' => round($cashAtHand, 2),
                'total_inflows' => round($inflows, 2),
                'total_outflows' => round($outflows, 2),
                'net_cash_flow' => round($netCashFlow, 2),
                'currency' => $currency
            ];
        }
        
        return $result;
    }

    /**
     * Get project-specific cash flow snapshot
     * 
     * Returns cash flow metrics for a specific project by filtering
     * cash transactions linked to the project's payment milestones.
     * 
     * @param int $projectId
     * @return array [
     *   'total_inflows' => float,
     *   'total_outflows' => float,
     *   'net_cash_flow' => float,
     *   'currency' => string
     * ]
     */
    public function getProjectCashFlow(int $projectId): array
    {
        // Get project currency
        $project = DB::table('projects')
            ->where('id', $projectId)
            ->first(['contract_currency']);
        
        if (!$project) {
            return [
                'total_inflows' => 0,
                'total_outflows' => 0,
                'net_cash_flow' => 0,
                'currency' => 'UGX'
            ];
        }
        
        $currency = $project->contract_currency;
        
        // Get inflows: cash transactions linked to project's payment milestones
        $inflows = DB::table('cash_transactions')
            ->join('payment_milestones', function ($join) {
                $join->on('cash_transactions.source_id', '=', 'payment_milestones.id')
                     ->where('cash_transactions.source_type', '=', 'payment_milestone');
            })
            ->where('payment_milestones.project_id', $projectId)
            ->where('cash_transactions.type', 'inflow')
            ->where('cash_transactions.currency', $currency)
            ->sum('cash_transactions.amount');
        
        // Get outflows: expenses linked to this project
        // Note: Currently expenses are not directly linked to projects
        // This would need to be implemented if project-specific expenses are needed
        $outflows = 0;
        
        $netCashFlow = $inflows - $outflows;
        
        return [
            'total_inflows' => round((float) $inflows, 2),
            'total_outflows' => round((float) $outflows, 2),
            'net_cash_flow' => round($netCashFlow, 2),
            'currency' => $currency
        ];
    }

    /**
     * Calculate monthly burn rate (average monthly outflows over last 3 months)
     * 
     * Returns FACT ONLY - average monthly outflows.
     * 
     * @param string $currency Currency code (USD, UGX)
     * @return float Average monthly outflows
     */
    public function calculateMonthlyBurnRate(string $currency): float
    {
        // Get date 3 months ago
        $threeMonthsAgo = date('Y-m-d', strtotime('-3 months'));
        
        // Get outflows for last 3 months
        $outflows = DB::table('cash_transactions')
            ->where('type', 'outflow')
            ->where('currency', $currency)
            ->where('transaction_date', '>=', $threeMonthsAgo)
            ->sum('amount');
        
        // Average over 3 months
        $burnRate = $outflows / 3;
        
        return round($burnRate, 2);
    }

    /**
     * Calculate cash runway in months
     * 
     * Formula from docs/_truth.md:
     * Cash Runway (months) = Cash at Hand / Average Monthly Burn
     * 
     * Returns FACT ONLY - months of runway based on current burn rate.
     * 
     * @param string $currency Currency code (USD, UGX)
     * @return float Months of runway (0 if burn rate is 0)
     */
    public function calculateCashRunway(string $currency): float
    {
        $snapshot = $this->getCashFlowSnapshot();
        
        if (!isset($snapshot['by_currency'][$currency])) {
            return 0;
        }
        
        $cashAtHand = $snapshot['by_currency'][$currency]['cash_at_hand'];
        $burnRate = $this->calculateMonthlyBurnRate($currency);
        
        if ($burnRate == 0) {
            return 0;
        }
        
        $runway = $cashAtHand / $burnRate;
        
        return round($runway, 1);
    }
}
