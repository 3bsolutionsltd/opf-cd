<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Lead Qualification Service
 *
 * Single responsibility: Calculate BANT-based lead qualification score.
 * Returns facts only — score, classification, breakdown, recommendation.
 * No CRUD operations, no decisions made on behalf of the caller.
 */
class LeadQualificationService
{
    /**
     * Calculate BANT qualification score for an opportunity.
     *
     * Score breakdown (100 points total):
     *   - Contract Value:  25 pts
     *   - Strategic Fit:   20 pts
     *   - Urgency:         15 pts
     *   - Authority:       20 pts
     *   - Need Validation: 20 pts
     *
     * Classification:
     *   - 70–100 → HOT
     *   - 40–69  → WARM
     *   - 0–39   → COLD
     *
     * @param int $opportunityId
     * @return array{
     *   score: int,
     *   classification: string,
     *   breakdown: array,
     *   recommendation: array
     * }|null  null when opportunity not found
     */
    public function calculateQualificationScore(int $opportunityId): ?array
    {
        $opp = DB::table('opportunities')->where('id', $opportunityId)->first();

        if (!$opp) {
            return null;
        }

        $score = 0;
        $breakdown = [];

        // Contract Value Score (25 points)
        if ($opp->estimated_value >= 100000) {
            $score += 25;
            $breakdown['contract_value'] = ['points' => 25, 'reason' => 'High value (>$100K)'];
        } elseif ($opp->estimated_value >= 50000) {
            $score += 15;
            $breakdown['contract_value'] = ['points' => 15, 'reason' => 'Medium value ($50K-$100K)'];
        } elseif ($opp->estimated_value >= 25000) {
            $score += 10;
            $breakdown['contract_value'] = ['points' => 10, 'reason' => 'Modest value ($25K-$50K)'];
        } else {
            $score += 5;
            $breakdown['contract_value'] = ['points' => 5, 'reason' => 'Low value (<$25K)'];
        }

        // Strategic Fit Score (20 points)
        switch ($opp->strategic_fit ?? 'cold_lead') {
            case 'existing_client':
                $score += 20;
                $breakdown['strategic_fit'] = ['points' => 20, 'reason' => 'Existing client'];
                break;
            case 'referral':
                $score += 15;
                $breakdown['strategic_fit'] = ['points' => 15, 'reason' => 'Client referral'];
                break;
            case 'target_industry':
                $score += 10;
                $breakdown['strategic_fit'] = ['points' => 10, 'reason' => 'Target industry'];
                break;
            default:
                $score += 5;
                $breakdown['strategic_fit'] = ['points' => 5, 'reason' => 'Cold lead'];
        }

        // Urgency Score (15 points)
        switch ($opp->timeline_urgency ?? 'unclear') {
            case 'immediate':
                $score += 15;
                $breakdown['urgency'] = ['points' => 15, 'reason' => 'Immediate need'];
                break;
            case 'this_quarter':
                $score += 10;
                $breakdown['urgency'] = ['points' => 10, 'reason' => 'This quarter'];
                break;
            default:
                $score += 5;
                $breakdown['urgency'] = ['points' => 5, 'reason' => 'Exploratory'];
        }

        // Authority Score (20 points)
        switch ($opp->authority_level ?? 'unknown') {
            case 'decision_maker':
                $score += 20;
                $breakdown['authority'] = ['points' => 20, 'reason' => 'Decision maker'];
                break;
            case 'influencer':
                $score += 10;
                $breakdown['authority'] = ['points' => 10, 'reason' => 'Influencer'];
                break;
            default:
                $breakdown['authority'] = ['points' => 0, 'reason' => 'Unknown contact'];
        }

        // Need Validation Score (20 points)
        switch ($opp->need_validation ?? 'unknown') {
            case 'critical':
                $score += 20;
                $breakdown['need'] = ['points' => 20, 'reason' => 'Critical need'];
                break;
            case 'important':
                $score += 10;
                $breakdown['need'] = ['points' => 10, 'reason' => 'Important'];
                break;
            default:
                $score += 5;
                $breakdown['need'] = ['points' => 5, 'reason' => 'Nice-to-have or unknown'];
        }

        $classification = $score >= 70 ? 'HOT' : ($score >= 40 ? 'WARM' : 'COLD');

        return [
            'score'          => $score,
            'classification' => $classification,
            'breakdown'      => $breakdown,
            'recommendation' => $this->getRecommendation($score),
        ];
    }

    /**
     * Return recommended action facts based on score.
     *
     * @param int $score
     * @return array{action: string, priority: string, message: string, suggested_stage: string}
     */
    private function getRecommendation(int $score): array
    {
        if ($score >= 70) {
            return [
                'action'          => 'qualify',
                'priority'        => 'HIGH',
                'message'         => 'HOT lead — consider moving to qualified stage',
                'suggested_stage' => 'qualified',
            ];
        }

        if ($score >= 40) {
            return [
                'action'          => 'review',
                'priority'        => 'MEDIUM',
                'message'         => 'WARM lead — schedule discovery call to validate BANT',
                'suggested_stage' => 'lead',
            ];
        }

        return [
            'action'          => 'nurture_or_disqualify',
            'priority'        => 'LOW',
            'message'         => 'COLD lead — consider nurture campaign or disqualify',
            'suggested_stage' => 'lead',
        ];
    }
}
