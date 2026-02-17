<?php

namespace App\Http\Controllers;

use App\Services\ProjectProgressService;
use App\Services\PaymentGapService;
use App\Services\ProjectHealthService;
use App\Services\CashFlowService;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    private ProjectProgressService $progressService;
    private PaymentGapService $paymentGapService;
    private ProjectHealthService $healthService;
    private CashFlowService $cashFlowService;

    public function __construct(
        ProjectProgressService $progressService,
        PaymentGapService $paymentGapService,
        ProjectHealthService $healthService,
        CashFlowService $cashFlowService
    ) {
        $this->progressService = $progressService;
        $this->paymentGapService = $paymentGapService;
        $this->healthService = $healthService;
        $this->cashFlowService = $cashFlowService;
    }

    public function getProgress(int $id): JsonResponse
    {
        return response()->json(
            $this->progressService->calculateProjectProgress($id)
        );
    }

    public function getPaymentGap(int $id): JsonResponse
    {
        $gap = $this->paymentGapService->calculatePaymentGap($id);
        
        // Add currency from project and map gap_amount to gap for view compatibility
        $project = \DB::table('projects')->where('id', $id)->first(['contract_currency']);
        $gap['gap'] = $gap['gap_amount'];
        $gap['currency'] = $project ? $project->contract_currency : 'UGX';
        
        return response()->json($gap);
    }

    public function getHealth(int $id): JsonResponse
    {
        $health = $this->healthService->getProjectHealth($id);
        
        // Map health_status to status for view compatibility
        $statusMap = [
            'green' => 'healthy',
            'amber' => 'at-risk',
            'red' => 'critical'
        ];
        
        $health['status'] = $statusMap[$health['health_status']] ?? 'at-risk';
        
        return response()->json($health);
    }

    public function getCashFlow(int $id): JsonResponse
    {
        return response()->json(
            $this->cashFlowService->getProjectCashFlow($id)
        );
    }
}
