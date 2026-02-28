<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\BusinessMetricsService;

/**
 * CalculateBusinessMetrics
 *
 * Scheduled command to calculate and persist business KPI metrics daily.
 * Runs at 1 AM via Laravel scheduler.
 *
 * Usage: php artisan metrics:calculate
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-007
 */
class CalculateBusinessMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store business KPI metrics for the current quarter';

    private BusinessMetricsService $metricsService;

    /**
     * Create a new command instance.
     */
    public function __construct(BusinessMetricsService $metricsService)
    {
        parent::__construct();
        $this->metricsService = $metricsService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting business metrics calculation...');

        $period = 'current_quarter';

        $metrics = [
            'opportunity_conversion_rate'     => $this->metricsService->calculateOpportunityConversionRate($period),
            'sales_velocity'                  => $this->metricsService->calculateSalesVelocity($period),
            'pipeline_value_by_stage'         => $this->metricsService->getPipelineValueByStage(),
            'opportunity_to_project_conversion'=> $this->metricsService->calculateOpportunityToProjectConversion($period),
            'average_deal_size'               => $this->metricsService->calculateAverageDealSize($period),
            'stage_conversion_rates'          => $this->metricsService->calculateStageConversionRates($period),
        ];

        $storedCount = 0;

        foreach ($metrics as $type => $data) {
            // Extract a single numeric value for storage (primary metric)
            $value = $this->extractPrimaryValue($type, $data);

            DB::table('business_metrics')->insert([
                'metric_type'   => $type,
                'period'        => $period,
                'metric_value'  => $value,
                'target_value'  => null,
                'status'        => 'on_track',
                'entity_filter' => null,
                'calculated_at' => now(),
                'created_at'    => now(),
            ]);

            $storedCount++;
            $this->line("  - {$type}: {$value}");
        }

        $this->info("Metrics calculation completed. Stored {$storedCount} metrics.");

        return 0;
    }

    /**
     * Extract the primary numeric value from a metric result array.
     *
     * @param  string $type
     * @param  array  $data
     * @return float
     */
    private function extractPrimaryValue(string $type, array $data): float
    {
        return match ($type) {
            'opportunity_conversion_rate'      => (float) ($data['conversion_rate'] ?? 0),
            'sales_velocity'                   => (float) ($data['sales_velocity'] ?? 0),
            'pipeline_value_by_stage'          => (float) ($data['total_pipeline_value'] ?? 0),
            'opportunity_to_project_conversion'=> (float) ($data['conversion_rate'] ?? 0),
            'average_deal_size'                => (float) ($data['average_deal_size'] ?? 0),
            'stage_conversion_rates'           => (float) ($data['stage_conversions'][0]['conversion_rate'] ?? 0),
            default                            => 0.0,
        };
    }
}
