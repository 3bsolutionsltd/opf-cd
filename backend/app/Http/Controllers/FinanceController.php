<?php

namespace App\Http\Controllers;

use App\Services\CashFlowService;
use App\Services\ExpenseSchedulerService;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    private CashFlowService $cashFlowService;
    private ExpenseSchedulerService $expenseService;

    public function __construct(
        CashFlowService $cashFlowService,
        ExpenseSchedulerService $expenseService
    ) {
        $this->cashFlowService = $cashFlowService;
        $this->expenseService = $expenseService;
    }

    public function getCashFlow(): JsonResponse
    {
        return response()->json(
            $this->cashFlowService->getCashFlowSnapshot()
        );
    }

    public function getUpcomingExpenses(): JsonResponse
    {
        return response()->json(
            $this->expenseService->getUpcomingExpenses()
        );
    }
}
