<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('dashboard')->group(function () {
    Route::get('/project-progress/{id}', [DashboardController::class, 'projectProgress']);
    Route::get('/payment-gap/{id}', [DashboardController::class, 'paymentGap']);
    Route::get('/project-health/{id}', [DashboardController::class, 'projectHealth']);
    Route::get('/cash-flow', [DashboardController::class, 'cashFlow']);
    Route::get('/upcoming-expenses', [DashboardController::class, 'upcomingExpenses']);
    Route::get('/sales-pipeline', [DashboardController::class, 'salesPipeline']);
});
