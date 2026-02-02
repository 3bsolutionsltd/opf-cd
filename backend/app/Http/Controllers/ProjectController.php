<?php

namespace App\Http\Controllers;

use App\Services\ProjectProgressService;
use App\Services\PaymentGapService;
use App\Services\ProjectHealthService;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    private ProjectProgressService $progressService;
    private PaymentGapService $paymentGapService;
    private ProjectHealthService $healthService;

    public function __construct(
        ProjectProgressService $progressService,
        PaymentGapService $paymentGapService,
        ProjectHealthService $healthService
    ) {
        $this->progressService = $progressService;
        $this->paymentGapService = $paymentGapService;
        $this->healthService = $healthService;
    }

    public function getProgress(int $id): JsonResponse
    {
        return response()->json(
            $this->progressService->calculateProjectProgress($id)
        );
    }

    public function getPaymentGap(int $id): JsonResponse
    {
        return response()->json(
            $this->paymentGapService->calculatePaymentGap($id)
        );
    }

    public function getHealth(int $id): JsonResponse
    {
        return response()->json(
            $this->healthService->getProjectHealth($id)
        );
    }
}
