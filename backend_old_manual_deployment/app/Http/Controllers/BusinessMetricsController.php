<?php

namespace App\Http\Controllers;

use App\Services\BusinessMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BusinessMetricsController
 *
 * Thin pass-through controller. Injects ONLY BusinessMetricsService.
 * Contains NO business logic, NO calculations, NO data transformations.
 *
 * Endpoints:
 *   GET /api/metrics/opportunity-conversion-rate?period={period}
 *   GET /api/metrics/sales-velocity?period={period}
 *   GET /api/metrics/pipeline-value
 *   GET /api/metrics/opportunity-to-project-conversion?period={period}
 *   GET /api/metrics/average-deal-size?period={period}
 *   GET /api/metrics/stage-conversion-rates?period={period}
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-005
 */
class BusinessMetricsController extends Controller
{
    private BusinessMetricsService $metricsService;

    public function __construct(BusinessMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * GET /api/metrics/opportunity-conversion-rate
     */
    public function getOpportunityConversionRate(Request $request): JsonResponse
    {
        $period = $request->input('period', 'current_quarter');
        $result = $this->metricsService->calculateOpportunityConversionRate($period);
        return response()->json($result);
    }

    /**
     * GET /api/metrics/sales-velocity
     */
    public function getSalesVelocity(Request $request): JsonResponse
    {
        $period = $request->input('period', 'current_quarter');
        $result = $this->metricsService->calculateSalesVelocity($period);
        return response()->json($result);
    }

    /**
     * GET /api/metrics/pipeline-value
     */
    public function getPipelineValue(): JsonResponse
    {
        $result = $this->metricsService->getPipelineValueByStage();
        return response()->json($result);
    }

    /**
     * GET /api/metrics/opportunity-to-project-conversion
     */
    public function getOpportunityToProjectConversion(Request $request): JsonResponse
    {
        $period = $request->input('period', 'current_quarter');
        $result = $this->metricsService->calculateOpportunityToProjectConversion($period);
        return response()->json($result);
    }

    /**
     * GET /api/metrics/average-deal-size
     */
    public function getAverageDealSize(Request $request): JsonResponse
    {
        $period = $request->input('period', 'current_quarter');
        $result = $this->metricsService->calculateAverageDealSize($period);
        return response()->json($result);
    }

    /**
     * GET /api/metrics/stage-conversion-rates
     */
    public function getStageConversionRates(Request $request): JsonResponse
    {
        $period = $request->input('period', 'current_quarter');
        $result = $this->metricsService->calculateStageConversionRates($period);
        return response()->json($result);
    }
}
