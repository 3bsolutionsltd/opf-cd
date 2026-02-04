<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\SalesController;

Route::get('/projects/{id}/progress', [ProjectController::class, 'getProgress']);
Route::get('/projects/{id}/payment-gap', [ProjectController::class, 'getPaymentGap']);
Route::get('/projects/{id}/health', [ProjectController::class, 'getHealth']);

Route::get('/finance/cash-flow', [FinanceController::class, 'getCashFlow']);
Route::get('/finance/expenses/upcoming', [FinanceController::class, 'getUpcomingExpenses']);

Route::get('/sales/pipeline', [SalesController::class, 'getPipeline']);
