<?php

namespace App\Http\Controllers;

use App\Services\AggregateDashboardService;
use Illuminate\Http\JsonResponse;

/**
 * AGGREGATE DASHBOARD CONTROLLER
 * 
 * Thin pass-through controller.
 * Returns aggregate dashboard metrics.
 * 
 * RULES:
 * - Calls ONE service only
 * - NO transformations
 * - NO calculations
 * - Returns service output directly
 */
class AggregateDashboardController extends Controller
{
    private AggregateDashboardService $aggregateDashboardService;
    
    public function __construct(AggregateDashboardService $aggregateDashboardService)
    {
        $this->aggregateDashboardService = $aggregateDashboardService;
    }
    
    /**
     * Get aggregate project progress.
     * 
     * Thin pass-through to AggregateDashboardService->getAggregateProgress().
     */
    public function getAggregateProgress(): JsonResponse
    {
        return response()->json(
            $this->aggregateDashboardService->getAggregateProgress()
        );
    }
    
    /**
     * Get aggregate payment gap.
     * 
     * Thin pass-through to AggregateDashboardService->getAggregatePaymentGap().
     */
    public function getAggregatePaymentGap(): JsonResponse
    {
        return response()->json(
            $this->aggregateDashboardService->getAggregatePaymentGap()
        );
    }
    
    /**
     * Get aggregate health breakdown.
     * 
     * Thin pass-through to AggregateDashboardService->getAggregateHealth().
     */
    public function getAggregateHealth(): JsonResponse
    {
        return response()->json(
            $this->aggregateDashboardService->getAggregateHealth()
        );
    }
}
