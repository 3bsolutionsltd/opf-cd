<?php

namespace App\Http\Controllers;

use App\Services\ExpenseManagementService;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Expense Controller
 * 
 * Thin pass-through controller for expense CRUD operations.
 * Separate from read-only dashboard controllers.
 * 
 * All business logic delegated to ExpenseManagementService.
 */
class ExpenseController extends Controller
{
    private ExpenseManagementService $expenseService;

    public function __construct(ExpenseManagementService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Display expenses list.
     */
    public function index(): View
    {
        return view('expenses.index');
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): View
    {
        return view('expenses.create');
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $result = $this->expenseService->createExpense($request->validated());

        return response()->json($result, $result['success'] ? 201 : 422);
    }

    /**
     * Show the form for editing an expense.
     */
    public function edit(int $expenseId): View
    {
        $expense = $this->expenseService->getExpenseDetails($expenseId);

        if (!$expense) {
            abort(404, 'Expense not found.');
        }

        return view('expenses.edit', [
            'expenseId' => $expenseId,
            'expense' => $expense,
        ]);
    }

    /**
     * Update an existing expense.
     */
    public function update(UpdateExpenseRequest $request, int $expenseId): JsonResponse
    {
        $result = $this->expenseService->updateExpense($expenseId, $request->validated());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Delete an expense.
     */
    public function destroy(int $expenseId): JsonResponse
    {
        $result = $this->expenseService->deleteExpense($expenseId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * API: Get all expenses.
     */
    public function apiIndex(?int $projectId = null): JsonResponse
    {
        $expenses = $this->expenseService->getExpenses($projectId);

        return response()->json([
            'success' => true,
            'expenses' => $expenses,
        ]);
    }

    /**
     * API: Get expense details.
     */
    public function apiShow(int $expenseId): JsonResponse
    {
        $expense = $this->expenseService->getExpenseDetails($expenseId);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'expense' => $expense,
        ]);
    }

    /**
     * API: Get expenses summary (amounts by status and currency).
     */
    public function getExpensesSummary(?int $projectId = null): JsonResponse
    {
        $summary = $this->expenseService->getExpensesSummary($projectId);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * API: Generate recurring expense instances.
     */
    public function generateRecurring(): JsonResponse
    {
        $result = $this->expenseService->generateRecurringExpenses();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * API: Update overdue expenses.
     */
    public function updateOverdue(): JsonResponse
    {
        $result = $this->expenseService->updateOverdueExpenses();

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
