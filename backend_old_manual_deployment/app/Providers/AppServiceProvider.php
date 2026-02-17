<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CashFlowService;
use App\Services\ExpenseSchedulerService;
use App\Services\PaymentGapService;
use App\Services\PipelineForecastService;
use App\Services\ProjectHealthService;
use App\Services\ProjectProgressService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProjectProgressService::class);
        $this->app->singleton(CashFlowService::class);
        $this->app->singleton(ExpenseSchedulerService::class);
        $this->app->singleton(PipelineForecastService::class);
        
        $this->app->singleton(PaymentGapService::class, function ($app) {
            return new PaymentGapService($app->make(ProjectProgressService::class));
        });
        
        $this->app->singleton(ProjectHealthService::class, function ($app) {
            return new ProjectHealthService(
                $app->make(ProjectProgressService::class),
                $app->make(PaymentGapService::class),
                $app->make(CashFlowService::class),
                $app->make(PipelineForecastService::class),
                $app->make(ExpenseSchedulerService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
