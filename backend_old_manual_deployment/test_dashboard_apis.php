<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Dashboard APIs ===\n\n";

// Test Sales Pipeline API
echo "1. Sales Pipeline API (/api/sales/pipeline):\n";
$pipelineService = app(\App\Services\PipelineForecastService::class);
$pipelineRaw = $pipelineService->getPipelineForecast();

// Transform like the controller does
$pipelineData = [
    'opportunities' => $pipelineRaw['by_stage'], // This is aggregated by stage, not individual opportunities
    'total_value' => $pipelineRaw['total_pipeline_value'],
    'weighted_value' => $pipelineRaw['weighted_pipeline_value'],
    'opportunity_count' => $pipelineRaw['opportunity_count']
];
echo json_encode($pipelineData, JSON_PRETTY_PRINT) . "\n\n";

// Test Upcoming Expenses API
echo "2. Upcoming Expenses API (/api/finance/expenses/upcoming):\n";
$expenseService = app(\App\Services\ExpenseSchedulerService::class);
$expensesArray = $expenseService->getUpcomingExpenses();
$totalAmount = array_sum(array_column($expensesArray, 'amount'));
$currency = !empty($expensesArray) ? $expensesArray[0]['currency'] : 'UGX';
$expensesData = [
    'expenses' => $expensesArray,
    'total_amount' => $totalAmount,
    'currency' => $currency
];
echo json_encode($expensesData, JSON_PRETTY_PRINT) . "\n\n";

// Test Cash Flow API
echo "3. Cash Flow API (/api/finance/cash-flow):\n";
$cashFlowService = app(\App\Services\CashFlowService::class);
$cashFlowData = $cashFlowService->getCashFlowSnapshot();
echo json_encode($cashFlowData, JSON_PRETTY_PRINT) . "\n";
