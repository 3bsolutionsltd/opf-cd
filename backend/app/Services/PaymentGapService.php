<?php

namespace App\Services;

/**
 * PaymentGapService
 * 
 * Calculates the Payment Gap for projects.
 * 
 * Formula:
 * Payment Gap = Project Progress % − Payment Received %
 * 
 * Rules:
 * - If Payment Gap > 20%, the project is financially at risk
 * - Payment received percentage = (Total Paid / Contract Value) × 100
 * - Project progress is calculated by ProjectProgressService
 * 
 * Source: docs/_truth.md
 */
class PaymentGapService
{
    /**
     * Calculate payment gap for a project
     * 
     * @param int $projectId
     * @return array ['gap' => float, 'is_at_risk' => bool, 'progress' => float, 'payment_received' => float]
     */
    public function calculatePaymentGap(int $projectId): array
    {
        // TODO: Implement payment gap calculation
        // 1. Get project progress from ProjectProgressService
        // 2. Calculate payment received percentage from payment_milestones
        // 3. Calculate gap = progress - payment_received
        // 4. Determine if at risk (gap > 20%)
        
        return [
            'gap' => 0.0,
            'is_at_risk' => false,
            'progress' => 0.0,
            'payment_received' => 0.0,
        ];
    }

    /**
     * Calculate payment received percentage for a project
     * 
     * @param int $projectId
     * @return float
     */
    public function calculatePaymentReceivedPercentage(int $projectId): float
    {
        // TODO: Implement
        // Payment Received % = (Total Paid / Contract Value) × 100
        // Sum all payment_milestones where status = 'paid'
        
        return 0.0;
    }

    /**
     * Get all projects at financial risk
     * 
     * @return array
     */
    public function getProjectsAtRisk(): array
    {
        // TODO: Implement
        // Return all projects where payment gap > 20%
        
        return [];
    }
}
