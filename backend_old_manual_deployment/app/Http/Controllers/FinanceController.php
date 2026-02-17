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
        $expenses = $this->expenseService->getUpcomingExpenses();
        
        // Calculate total amount (assuming all expenses in same currency)
        $totalAmount = array_sum(array_column($expenses, 'amount'));
        $currency = !empty($expenses) ? $expenses[0]['currency'] : 'UGX';
        
        return response()->json([
            'expenses' => $expenses,
            'total_amount' => $totalAmount,
            'currency' => $currency
        ]);
    }
}
