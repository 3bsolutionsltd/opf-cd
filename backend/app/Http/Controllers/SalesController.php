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
        return response()->json(
            $this->pipelineService->getPipelineForecast()
        );
    }
}
