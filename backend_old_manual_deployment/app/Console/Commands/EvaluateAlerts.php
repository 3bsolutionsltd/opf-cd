<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AlertService;

/**
 * EvaluateAlerts
 * 
 * Scheduled command to evaluate all alert conditions and generate alerts.
 * Runs daily via Laravel scheduler.
 * 
 * Usage: php artisan alerts:evaluate
 */
class EvaluateAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:evaluate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate all alert conditions and generate system alerts';

    private AlertService $alertService;

    /**
     * Create a new command instance.
     */
    public function __construct(AlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting alert evaluation...');

        try {
            $result = $this->alertService->evaluateAllAlerts();

            $this->info("Alert evaluation completed:");
            $this->line("  - Total alerts created: {$result['alerts_created']}");
            
            foreach ($result['alerts_by_type'] as $type => $count) {
                $this->line("  - {$type}: {$count} active");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Alert evaluation failed: " . $e->getMessage());
            return 1;
        }
    }
}
