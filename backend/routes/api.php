<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AggregateDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectManagementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HealthCheckController;

// Health check endpoint (public, no authentication required)
Route::get('/health', [HealthCheckController::class, 'check']);

// All API routes need web middleware for session-based auth
Route::middleware(['web'])->group(function () {

// Authentication endpoints (protected)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/user/permissions', [AuthController::class, 'getPermissions']);
    Route::get('/user', [AuthController::class, 'getCurrentUser']);
});

// Dashboard endpoints (protected)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'getSummary']);
    Route::get('/dashboard/aggregate/progress', [AggregateDashboardController::class, 'getAggregateProgress']);
    Route::get('/dashboard/aggregate/payment-gap', [AggregateDashboardController::class, 'getAggregatePaymentGap']);
    Route::get('/dashboard/aggregate/health', [AggregateDashboardController::class, 'getAggregateHealth']);
});

// Project endpoints (protected)
Route::middleware(['check.permission:projects,view'])->group(function () {
    Route::get('/projects/{id}/progress', [ProjectController::class, 'getProgress']);
    Route::get('/projects/{id}/payment-gap', [ProjectController::class, 'getPaymentGap']);
    Route::get('/projects/{id}/health', [ProjectController::class, 'getHealth']);
    Route::get('/projects/{id}/cash-flow', [ProjectController::class, 'getCashFlow']);
});

// Finance endpoints (protected)
Route::middleware(['check.permission:accounts,view'])->group(function () {
    Route::get('/finance/cash-flow', [FinanceController::class, 'getCashFlow']);
    Route::get('/finance/expenses/upcoming', [FinanceController::class, 'getUpcomingExpenses']);
});

// Sales endpoints (protected)
Route::middleware(['check.permission:opportunities,view'])->group(function () {
    Route::get('/sales/pipeline', [SalesController::class, 'getPipeline']);
});

// Projects management endpoints (protected)
Route::middleware(['check.permission:projects,view'])->group(function () {
    Route::get('/projects', [ProjectManagementController::class, 'apiIndex']);
    Route::get('/projects/{id}', [ProjectManagementController::class, 'apiShow']);
    Route::get('/projects/{id}/has-payments', [ProjectManagementController::class, 'hasPayments']);
});

Route::middleware(['check.permission:projects,create'])->group(function () {
    Route::post('/projects', [ProjectManagementController::class, 'store']);
});

Route::middleware(['check.permission:projects,edit'])->group(function () {
    Route::put('/projects/{id}', [ProjectManagementController::class, 'update']);
});

Route::middleware(['check.permission:projects,delete'])->group(function () {
    Route::delete('/projects/{id}', [ProjectManagementController::class, 'destroy']);
});

// Tasks management endpoints (protected)
Route::middleware(['check.permission:tasks,view'])->group(function () {
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'apiIndex']);
    Route::get('/tasks/{taskId}', [TaskController::class, 'apiShow']);
    Route::get('/projects/{projectId}/tasks/weight-sum', [TaskController::class, 'getWeightSum']);
});

Route::middleware(['check.permission:tasks,create'])->group(function () {
    Route::post('/projects/{projectId}/tasks', [TaskController::class, 'store']);
});

Route::middleware(['check.permission:tasks,edit'])->group(function () {
    Route::put('/tasks/{taskId}', [TaskController::class, 'update']);
});

Route::middleware(['check.permission:tasks,delete'])->group(function () {
    Route::delete('/tasks/{taskId}', [TaskController::class, 'destroy']);
});

// Milestones management endpoints (protected)
Route::middleware(['check.permission:milestones,view'])->group(function () {
    Route::get('/projects/{projectId}/milestones', [MilestoneController::class, 'apiIndex']);
    Route::get('/milestones/{milestoneId}', [MilestoneController::class, 'apiShow']);
    Route::get('/projects/{projectId}/milestones/summary', [MilestoneController::class, 'getMilestonesSummary']);
});

Route::middleware(['check.permission:milestones,create'])->group(function () {
    Route::post('/projects/{projectId}/milestones', [MilestoneController::class, 'store']);
});

Route::middleware(['check.permission:milestones,edit'])->group(function () {
    Route::put('/milestones/{milestoneId}', [MilestoneController::class, 'update']);
    Route::post('/milestones/{milestoneId}/record-payment', [MilestoneController::class, 'recordPayment']);
});

Route::middleware(['check.permission:milestones,delete'])->group(function () {
    Route::delete('/milestones/{milestoneId}', [MilestoneController::class, 'destroy']);
});

// Expenses management endpoints (protected)
Route::middleware(['check.permission:expenses,view'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'apiIndex']);
    Route::get('/expenses/summary', [ExpenseController::class, 'getExpensesSummary']);
    Route::get('/expenses/{expenseId}', [ExpenseController::class, 'apiShow']);
});

Route::middleware(['check.permission:expenses,create'])->group(function () {
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::post('/expenses/generate-recurring', [ExpenseController::class, 'generateRecurring']);
});

Route::middleware(['check.permission:expenses,edit'])->group(function () {
    Route::put('/expenses/{expenseId}', [ExpenseController::class, 'update']);
    // Note: update-overdue route removed - overdue is now calculated dynamically in service layer
});

Route::middleware(['check.permission:expenses,delete'])->group(function () {
    Route::delete('/expenses/{expenseId}', [ExpenseController::class, 'destroy']);
});

// Opportunities management endpoints (protected)
Route::middleware(['check.permission:opportunities,view'])->group(function () {
    Route::get('/opportunities', [OpportunityController::class, 'apiIndex']);
    Route::get('/opportunities/{opportunityId}', [OpportunityController::class, 'apiShow']);
});

Route::middleware(['check.permission:opportunities,create'])->group(function () {
    Route::post('/opportunities', [OpportunityController::class, 'store']);
});

Route::middleware(['check.permission:opportunities,edit'])->group(function () {
    Route::put('/opportunities/{opportunityId}', [OpportunityController::class, 'update']);
});

Route::middleware(['check.permission:opportunities,delete'])->group(function () {
    Route::delete('/opportunities/{opportunityId}', [OpportunityController::class, 'destroy']);
});

// Accounts management endpoints (protected)
Route::middleware(['check.permission:accounts,view'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'apiIndex']);
    Route::get('/accounts/{accountId}', [AccountController::class, 'apiShow']);
});

Route::middleware(['check.permission:accounts,create'])->group(function () {
    Route::post('/accounts', [AccountController::class, 'store']);
});

Route::middleware(['check.permission:accounts,edit'])->group(function () {
    Route::put('/accounts/{accountId}', [AccountController::class, 'update']);
});

Route::middleware(['check.permission:accounts,delete'])->group(function () {
    Route::delete('/accounts/{accountId}', [AccountController::class, 'destroy']);
});

// Cash Transactions management endpoints (protected)
Route::middleware(['check.permission:cash_transactions,view'])->group(function () {
    Route::get('/cash-transactions', [CashTransactionController::class, 'apiIndex']);
    Route::get('/cash-transactions/{transactionId}', [CashTransactionController::class, 'apiShow']);
});

Route::middleware(['check.permission:cash_transactions,create'])->group(function () {
    Route::post('/cash-transactions', [CashTransactionController::class, 'store']);
});

// Alerts management endpoints (protected)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/count', [AlertController::class, 'count']);
});

Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::post('/alerts/{alertId}/dismiss', [AlertController::class, 'dismiss']);
});

// Audit log endpoints (protected - Admin only)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/audit-logs', [AuditController::class, 'index']);
    Route::get('/audit-logs/stats', [AuditController::class, 'stats']);
    Route::get('/audit-logs/entity/{entityType}/{entityId}', [AuditController::class, 'entity']);
    Route::get('/audit-logs/user/{userId}', [AuditController::class, 'user']);
});

// Report export endpoints (protected)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/reports/export/dashboard', [ReportController::class, 'exportDashboard']);
    Route::get('/reports/export/projects', [ReportController::class, 'exportProjects']);
    Route::get('/reports/export/cash-flow', [ReportController::class, 'exportCashFlow']);
    Route::get('/reports/export/opportunities', [ReportController::class, 'exportOpportunities']);
    Route::get('/reports/export/expenses', [ReportController::class, 'exportExpenses']);
    Route::get('/reports/export/audit-logs', [ReportController::class, 'exportAuditLogs']);
    Route::get('/reports/export/project-health', [ReportController::class, 'exportProjectHealth']);
});

}); // End web middleware group
