<?php

namespace App\Services;

/**
 * CashFlowService
 * 
 * Manages cash flow calculations and reporting.
 * 
 * Formulas:
 * - Cash at Hand = Opening Balance + Inflows − Outflows
 * - Cash Runway (months) = Cash at Hand / Average Monthly Burn
 * 
 * Rules:
 * - Current balance is calculated, not stored
 * - Inflows and outflows come from cash_transactions
 * - Opening balance comes from accounts table
 * 
 * Source: docs/_truth.md
 */
class CashFlowService
{
    /**
     * Calculate cash at hand for an account
     * 
     * @param int $accountId
     * @return float
     */
    public function calculateCashAtHand(int $accountId): float
    {
        // TODO: Implement
        // Cash at Hand = Opening Balance + Inflows − Outflows
        // Get opening_balance from accounts
        // Sum all inflows from cash_transactions where type = 'inflow'
        // Sum all outflows from cash_transactions where type = 'outflow'
        
        return 0.0;
    }

    /**
     * Calculate cash runway in months
     * 
     * @param int $accountId
     * @return float|null Returns null if average monthly burn is 0
     */
    public function calculateCashRunway(int $accountId): ?float
    {
        // TODO: Implement
        // Cash Runway (months) = Cash at Hand / Average Monthly Burn
        // Calculate average monthly burn from last 3-6 months of outflows
        
        return null;
    }

    /**
     * Get total cash at hand across all accounts
     * 
     * @param string|null $currency Filter by currency
     * @return float
     */
    public function getTotalCashAtHand(?string $currency = null): float
    {
        // TODO: Implement
        // Sum cash at hand for all accounts
        // If currency specified, filter by that currency
        // Otherwise sum all (may need currency conversion)
        
        return 0.0;
    }

    /**
     * Calculate average monthly burn rate
     * 
     * @param int $accountId
     * @param int $months Number of months to average
     * @return float
     */
    public function calculateAverageMonthlyBurn(int $accountId, int $months = 3): float
    {
        // TODO: Implement
        // Get all outflows for the specified number of months
        // Calculate average per month
        
        return 0.0;
    }

    /**
     * Get cash flow summary for reporting
     * 
     * @param int $accountId
     * @return array
     */
    public function getCashFlowSummary(int $accountId): array
    {
        // TODO: Implement
        
        return [
            'opening_balance' => 0.0,
            'total_inflows' => 0.0,
            'total_outflows' => 0.0,
            'cash_at_hand' => 0.0,
            'average_monthly_burn' => 0.0,
            'cash_runway_months' => null,
        ];
    }
}
