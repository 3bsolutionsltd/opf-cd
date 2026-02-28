<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * BusinessMetricsService
 *
 * Single responsibility: Calculate opportunity-based KPI metrics.
 * Returns facts only — no decisions, no business logic beyond calculation.
 *
 * Period formats supported:
 *   - 'Q1_2026', 'Q2_2026', etc. (quarterly)
 *   - '2026-02' (monthly)
 *   - 'weekly_2026-W08' (weekly)
 *   - 'current_quarter' (resolves to current calendar quarter)
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-003
 */
class BusinessMetricsService
{
    /**
     * Resolve a period string to start/end date boundaries.
     *
     * @param  string $period
     * @return array{start: string, end: string}
     */
    private function resolvePeriodDates(string $period): array
    {
        $now = new \DateTime();

        if ($period === 'current_quarter') {
            $month = (int) $now->format('n');
            $year  = (int) $now->format('Y');
            $quarter = (int) ceil($month / 3);
            $period = 'Q' . $quarter . '_' . $year;
        }

        // Quarterly: Q1_2026
        if (preg_match('/^Q([1-4])_(\d{4})$/', $period, $m)) {
            $quarter = (int) $m[1];
            $year    = (int) $m[2];
            $startMonth = (($quarter - 1) * 3) + 1;
            $endMonth   = $startMonth + 2;
            $start = sprintf('%04d-%02d-01', $year, $startMonth);
            $end   = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
            return ['start' => $start, 'end' => $end];
        }

        // Monthly: 2026-02
        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            $start = $period . '-01';
            $end   = date('Y-m-t', strtotime($start));
            return ['start' => $start, 'end' => $end];
        }

        // Weekly: weekly_2026-W08
        if (preg_match('/^weekly_(\d{4})-W(\d{2})$/', $period, $m)) {
            $dto = new \DateTime();
            $dto->setISODate((int) $m[1], (int) $m[2], 1);
            $start = $dto->format('Y-m-d');
            $dto->setISODate((int) $m[1], (int) $m[2], 7);
            $end = $dto->format('Y-m-d');
            return ['start' => $start, 'end' => $end];
        }

        // Fallback: current month
        return [
            'start' => date('Y-m-01'),
            'end'   => date('Y-m-t'),
        ];
    }

    /**
     * Calculate opportunity conversion rate for a given period.
     *
     * Conversion Rate = (Won Opportunities / Total Closed Opportunities) × 100
     * "Closed" means stage is 'won' or 'lost'.
     *
     * @param  string $period
     * @return array{
     *   period: string,
     *   total_closed: int,
     *   total_won: int,
     *   conversion_rate: float,
     *   metric_type: string
     * }
     */
    public function calculateOpportunityConversionRate(string $period): array
    {
        $dates = $this->resolvePeriodDates($period);

        $totalClosed = DB::table('opportunities')
            ->whereIn('stage', ['won', 'lost'])
            ->whereBetween('updated_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->count();

        $totalWon = DB::table('opportunities')
            ->where('stage', 'won')
            ->whereBetween('updated_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->count();

        $conversionRate = $totalClosed > 0
            ? round(($totalWon / $totalClosed) * 100, 2)
            : 0.0;

        return [
            'period'          => $period,
            'total_closed'    => $totalClosed,
            'total_won'       => $totalWon,
            'conversion_rate' => $conversionRate,
            'metric_type'     => 'opportunity_conversion_rate',
        ];
    }

    /**
     * Calculate sales velocity for a given period.
     *
     * Sales Velocity = (Won Opportunities × Average Deal Size × Win Rate) / Average Sales Cycle Days
     * Returns 0 if no won opportunities exist in the period.
     *
     * @param  string $period
     * @return array{
     *   period: string,
     *   won_count: int,
     *   average_deal_size: float,
     *   win_rate: float,
     *   average_cycle_days: float,
     *   sales_velocity: float,
     *   metric_type: string
     * }
     */
    public function calculateSalesVelocity(string $period): array
    {
        $dates = $this->resolvePeriodDates($period);

        $wonOpportunities = DB::table('opportunities')
            ->where('stage', 'won')
            ->whereBetween('updated_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->select('estimated_value', 'created_at', 'updated_at')
            ->get();

        $wonCount = $wonOpportunities->count();

        if ($wonCount === 0) {
            return [
                'period'             => $period,
                'won_count'          => 0,
                'average_deal_size'  => 0.0,
                'win_rate'           => 0.0,
                'average_cycle_days' => 0.0,
                'sales_velocity'     => 0.0,
                'metric_type'        => 'sales_velocity',
            ];
        }

        $totalValue   = $wonOpportunities->sum('estimated_value');
        $avgDealSize  = round($totalValue / $wonCount, 2);

        $totalCycleDays = 0.0;
        foreach ($wonOpportunities as $opp) {
            $created  = strtotime($opp->created_at);
            $closed   = strtotime($opp->updated_at);
            $totalCycleDays += max(1, ($closed - $created) / 86400);
        }
        $avgCycleDays = round($totalCycleDays / $wonCount, 2);

        $conversionData = $this->calculateOpportunityConversionRate($period);
        $winRate        = $conversionData['conversion_rate'];

        $salesVelocity = $avgCycleDays > 0
            ? round(($wonCount * $avgDealSize * ($winRate / 100)) / $avgCycleDays, 2)
            : 0.0;

        return [
            'period'             => $period,
            'won_count'          => $wonCount,
            'average_deal_size'  => $avgDealSize,
            'win_rate'           => $winRate,
            'average_cycle_days' => $avgCycleDays,
            'sales_velocity'     => $salesVelocity,
            'metric_type'        => 'sales_velocity',
        ];
    }

    /**
     * Get pipeline value grouped by opportunity stage.
     *
     * Only includes non-lost opportunities with an expected close date >= today.
     *
     * @return array{
     *   by_stage: array,
     *   total_pipeline_value: float,
     *   weighted_pipeline_value: float,
     *   metric_type: string
     * }
     */
    public function getPipelineValueByStage(): array
    {
        $today = date('Y-m-d');

        $rows = DB::table('opportunities')
            ->where('stage', '!=', 'lost')
            ->where('expected_close_date', '>=', $today)
            ->select('stage', 'estimated_value', 'probability')
            ->get();

        $byStage            = [];
        $totalPipelineValue = 0.0;
        $weightedValue      = 0.0;

        foreach ($rows as $row) {
            $stage = $row->stage;
            if (!isset($byStage[$stage])) {
                $byStage[$stage] = [
                    'stage'          => $stage,
                    'count'          => 0,
                    'total_value'    => 0.0,
                    'weighted_value' => 0.0,
                ];
            }

            $oppWeighted = $row->estimated_value * ($row->probability / 100);

            $byStage[$stage]['count']++;
            $byStage[$stage]['total_value']    += $row->estimated_value;
            $byStage[$stage]['weighted_value'] += $oppWeighted;

            $totalPipelineValue += $row->estimated_value;
            $weightedValue      += $oppWeighted;
        }

        foreach ($byStage as &$s) {
            $s['total_value']    = round($s['total_value'], 2);
            $s['weighted_value'] = round($s['weighted_value'], 2);
        }

        return [
            'by_stage'               => array_values($byStage),
            'total_pipeline_value'   => round($totalPipelineValue, 2),
            'weighted_pipeline_value'=> round($weightedValue, 2),
            'metric_type'            => 'pipeline_value_by_stage',
        ];
    }

    /**
     * Calculate the rate at which won opportunities are converted into projects.
     *
     * Conversion = (Won opportunities WITH linked project / Total won opportunities) × 100
     *
     * @param  string $period
     * @return array{
     *   period: string,
     *   total_won: int,
     *   converted_to_project: int,
     *   conversion_rate: float,
     *   metric_type: string
     * }
     */
    public function calculateOpportunityToProjectConversion(string $period): array
    {
        $dates = $this->resolvePeriodDates($period);

        $wonIds = DB::table('opportunities')
            ->where('stage', 'won')
            ->whereBetween('updated_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->pluck('id');

        $totalWon = $wonIds->count();

        if ($totalWon === 0) {
            return [
                'period'               => $period,
                'total_won'            => 0,
                'converted_to_project' => 0,
                'conversion_rate'      => 0.0,
                'metric_type'          => 'opportunity_to_project_conversion',
            ];
        }

        $convertedCount = DB::table('projects')
            ->whereIn('opportunity_id', $wonIds)
            ->distinct('opportunity_id')
            ->count('opportunity_id');

        $conversionRate = round(($convertedCount / $totalWon) * 100, 2);

        return [
            'period'               => $period,
            'total_won'            => $totalWon,
            'converted_to_project' => $convertedCount,
            'conversion_rate'      => $conversionRate,
            'metric_type'          => 'opportunity_to_project_conversion',
        ];
    }

    /**
     * Calculate average deal size for won opportunities in a period.
     *
     * Average Deal Size = Total Won Value / Count of Won Opportunities
     *
     * @param  string $period
     * @return array{
     *   period: string,
     *   won_count: int,
     *   total_won_value: float,
     *   average_deal_size: float,
     *   metric_type: string
     * }
     */
    public function calculateAverageDealSize(string $period): array
    {
        $dates = $this->resolvePeriodDates($period);

        $result = DB::table('opportunities')
            ->where('stage', 'won')
            ->whereBetween('updated_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->selectRaw('COUNT(*) as won_count, COALESCE(SUM(estimated_value), 0) as total_won_value')
            ->first();

        $wonCount      = (int) ($result->won_count ?? 0);
        $totalWonValue = (float) ($result->total_won_value ?? 0.0);
        $avgDealSize   = $wonCount > 0 ? round($totalWonValue / $wonCount, 2) : 0.0;

        return [
            'period'           => $period,
            'won_count'        => $wonCount,
            'total_won_value'  => round($totalWonValue, 2),
            'average_deal_size'=> $avgDealSize,
            'metric_type'      => 'average_deal_size',
        ];
    }

    /**
     * Calculate conversion rates for each stage transition in a period.
     *
     * Stage order: lead → qualified → proposal → negotiation → won/lost
     * Conversion rate per stage = count entering next stage / count in current stage
     *
     * @param  string $period
     * @return array{
     *   period: string,
     *   stage_counts: array,
     *   stage_conversions: array,
     *   metric_type: string
     * }
     */
    public function calculateStageConversionRates(string $period): array
    {
        $dates = $this->resolvePeriodDates($period);

        $stageCounts = DB::table('opportunities')
            ->whereBetween('created_at', [$dates['start'] . ' 00:00:00', $dates['end'] . ' 23:59:59'])
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        $stageOrder = ['lead', 'qualified', 'proposal', 'negotiation', 'won'];
        $stageConversions = [];

        foreach ($stageOrder as $i => $stage) {
            $count = (int) ($stageCounts[$stage] ?? 0);
            if ($i < count($stageOrder) - 1) {
                $nextStage   = $stageOrder[$i + 1];
                $nextCount   = (int) ($stageCounts[$nextStage] ?? 0);
                $convRate    = $count > 0 ? round(($nextCount / $count) * 100, 2) : 0.0;

                $stageConversions[] = [
                    'from_stage'      => $stage,
                    'to_stage'        => $nextStage,
                    'from_count'      => $count,
                    'to_count'        => $nextCount,
                    'conversion_rate' => $convRate,
                ];
            }
        }

        return [
            'period'            => $period,
            'stage_counts'      => $stageCounts,
            'stage_conversions' => $stageConversions,
            'metric_type'       => 'stage_conversion_rates',
        ];
    }
}
