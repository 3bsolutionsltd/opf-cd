<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectManagementController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CashTransactionController;

// Authentication routes (public)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Dashboard routes (protected)
Route::middleware(['check.permission:dashboards,view'])->group(function () {
    Route::get('/', function () {
        return view('dashboard.index');
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/project-progress/{id}', [DashboardController::class, 'projectProgress']);
        Route::get('/payment-gap/{id}', [DashboardController::class, 'paymentGap']);
        Route::get('/project-health/{id}', [DashboardController::class, 'projectHealth']);
        Route::get('/cash-flow', [DashboardController::class, 'cashFlow']);
        Route::get('/upcoming-expenses', [DashboardController::class, 'upcomingExpenses']);
        Route::get('/sales-pipeline', [DashboardController::class, 'salesPipeline']);
    });
});

// Projects management routes (protected)
Route::middleware(['check.permission:projects,view'])->group(function () {
    Route::get('/projects', [ProjectManagementController::class, 'index'])->name('projects.index');
});

Route::middleware(['check.permission:projects,create'])->group(function () {
    Route::get('/projects/create', [ProjectManagementController::class, 'create'])->name('projects.create');
});

Route::middleware(['check.permission:projects,edit'])->group(function () {
    Route::get('/projects/{id}/edit', [ProjectManagementController::class, 'edit'])->name('projects.edit');
});

Route::middleware(['check.permission:projects,view'])->group(function () {
    Route::get('/projects/{id}', [ProjectManagementController::class, 'show'])->name('projects.show');
});

// Tasks management routes (protected)
Route::middleware(['check.permission:tasks,view'])->group(function () {
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'index'])->name('tasks.index');
});

Route::middleware(['check.permission:tasks,create'])->group(function () {
    Route::get('/projects/{projectId}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
});

Route::middleware(['check.permission:tasks,edit'])->group(function () {
    Route::get('/tasks/{taskId}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
});

// Milestones management routes (protected)
Route::middleware(['check.permission:milestones,view'])->group(function () {
    Route::get('/projects/{projectId}/milestones', [MilestoneController::class, 'index'])->name('milestones.index');
});

Route::middleware(['check.permission:milestones,create'])->group(function () {
    Route::get('/projects/{projectId}/milestones/create', [MilestoneController::class, 'create'])->name('milestones.create');
});

Route::middleware(['check.permission:milestones,edit'])->group(function () {
    Route::get('/milestones/{milestoneId}/edit', [MilestoneController::class, 'edit'])->name('milestones.edit');
});

// Expenses management routes (protected)
Route::middleware(['check.permission:expenses,view'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
});

Route::middleware(['check.permission:expenses,create'])->group(function () {
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
});

Route::middleware(['check.permission:expenses,edit'])->group(function () {
    Route::get('/expenses/{expenseId}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
});

// Opportunities management routes (protected)
Route::middleware(['check.permission:opportunities,view'])->group(function () {
    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities.index');
});

Route::middleware(['check.permission:opportunities,create'])->group(function () {
    Route::get('/opportunities/create', [OpportunityController::class, 'create'])->name('opportunities.create');
});

Route::middleware(['check.permission:opportunities,edit'])->group(function () {
    Route::get('/opportunities/{opportunityId}/edit', [OpportunityController::class, 'edit'])->name('opportunities.edit');
});

// Accounts management routes (protected)
Route::middleware(['check.permission:accounts,view'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
});

Route::middleware(['check.permission:accounts,create'])->group(function () {
    Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
});

Route::middleware(['check.permission:accounts,edit'])->group(function () {
    Route::get('/accounts/{accountId}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
});

// Cash Transactions management routes (protected)
Route::middleware(['check.permission:cash_transactions,view'])->group(function () {
    Route::get('/cash-transactions', [CashTransactionController::class, 'index'])->name('cash-transactions.index');
});

Route::middleware(['check.permission:cash_transactions,create'])->group(function () {
    Route::get('/cash-transactions/create', [CashTransactionController::class, 'create'])->name('cash-transactions.create');
});
