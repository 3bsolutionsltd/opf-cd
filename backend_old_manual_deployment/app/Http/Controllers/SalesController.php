<?php

namespace App\Http\Controllers;

use App\Services\PipelineForecastService;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    private PipelineForecastService $pipelineService;

    public function __construct(PipelineForecastService $pipelineService)
    {
        $this->pipelineService = $pipelineService;
    }

    public function getPipeline(): JsonResponse
    {
        $forecast = $this->pipelineService->getPipelineForecast();
        
        // Transform to match dashboard view expectations
        return response()->json([
            'opportunities' => $forecast['by_stage'], // Array of stages with counts
            'total_value' => $forecast['total_pipeline_value'],
            'weighted_value' => $forecast['weighted_pipeline_value'],
            'opportunity_count' => $forecast['opportunity_count'],
            'by_currency' => $forecast['by_currency']
        ]);
    }
}
