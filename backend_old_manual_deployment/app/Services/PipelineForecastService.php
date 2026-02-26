<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * PipelineForecastService
 * 
 * Calculates weighted pipeline value for sales forecasting.
 * 
 * Formula:
 * Weighted Pipeline Value = Σ(opportunity.estimated_value × probability / 100)
 * 
 * Rules:
 * - Only includes opportunities where stage != 'lost' and expected_close_date >= today
 * - Probability is a percentage (0-100)
 * - Pipeline calculations performed per currency
 * - No currency conversion applied
 * 
 * Source: docs/_truth.md
 */
class PipelineForecastService
{
    /**
     * Get pipeline forecast with aggregated metrics and breakdown by stage and currency
     * 
     * @return array [
     *   'by_currency' => [
     *     'UGX' => ['total_value' => float, 'weighted_value' => float, 'count' => int],
     *     'USD' => ['total_value' => float, 'weighted_value' => float, 'count' => int]
     *   ],
     *   'total_pipeline_value' => float,
     *   'weighted_pipeline_value' => float,
     *   'opportunity_count' => int,
     *   'by_stage' => [
     *     ['stage' => string, 'count' => int, 'total_value' => float, 'weighted_value' => float],
     *     ...
     *   ]
     * ]
     */
    public function getPipelineForecast(): array
    {
        $today = date('Y-m-d');

        // Get all opportunities where stage != 'lost' and expected_close_date >= today
        $opportunities = DB::table('opportunities')
            ->where('stage', '!=', 'lost')
            ->where('expected_close_date', '>=', $today)
            ->select('stage', 'estimated_value', 'probability', 'currency')
            ->get();

        // Calculate overall metrics
        $totalPipelineValue = 0.0;
        $weightedPipelineValue = 0.0;
        $opportunityCount = $opportunities->count();

        // Group by currency
        $byCurrency = [
            'UGX' => ['total_value' => 0.0, 'weighted_value' => 0.0, 'count' => 0],
            'USD' => ['total_value' => 0.0, 'weighted_value' => 0.0, 'count' => 0]
        ];

        // Group by stage for breakdown
        $byStage = [];

        foreach ($opportunities as $opportunity) {
            $currency = $opportunity->currency ?? 'UGX';
            
            // Aggregate totals
            $totalPipelineValue += $opportunity->estimated_value;
            
            // Weighted Pipeline Value = estimated_value × (probability / 100)
            $weightedValue = $opportunity->estimated_value * ($opportunity->probability / 100);
            $weightedPipelineValue += $weightedValue;

            // Group by currency
            if (!isset($byCurrency[$currency])) {
                $byCurrency[$currency] = ['total_value' => 0.0, 'weighted_value' => 0.0, 'count' => 0];
            }
            $byCurrency[$currency]['total_value'] += $opportunity->estimated_value;
            $byCurrency[$currency]['weighted_value'] += $weightedValue;
            $byCurrency[$currency]['count']++;

            // Group by stage
            if (!isset($byStage[$opportunity->stage])) {
                $byStage[$opportunity->stage] = [
                    'stage' => $opportunity->stage,
                    'count' => 0,
                    'total_value' => 0.0,
                    'weighted_value' => 0.0,
                ];
            }

            $byStage[$opportunity->stage]['count']++;
            $byStage[$opportunity->stage]['total_value'] += $opportunity->estimated_value;
            $byStage[$opportunity->stage]['weighted_value'] += $weightedValue;
        }

        // Round currency values
        foreach ($byCurrency as $curr => &$data) {
            $data['total_value'] = round($data['total_value'], 2);
            $data['weighted_value'] = round($data['weighted_value'], 2);
        }

        // Convert byStage to array and round values
        $stageBreakdown = array_values($byStage);
        foreach ($stageBreakdown as &$stage) {
            $stage['total_value'] = round($stage['total_value'], 2);
            $stage['weighted_value'] = round($stage['weighted_value'], 2);
        }

        return [
            'by_currency' => $byCurrency,
            'total_pipeline_value' => round($totalPipelineValue, 2),
            'weighted_pipeline_value' => round($weightedPipelineValue, 2),
            'opportunity_count' => $opportunityCount,
            'by_stage' => $stageBreakdown,
        ];
    }
}
