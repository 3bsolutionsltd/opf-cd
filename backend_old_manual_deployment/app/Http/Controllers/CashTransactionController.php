<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashTransactionRequest;
use App\Services\CashTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cash Transaction Controller
 * 
 * Thin pass-through controller - NO transformations, NO calculations.
 * Calls ONE service (CashTransactionService).
 */
class CashTransactionController extends Controller
{
    /**
     * Injected service
     */
    private CashTransactionService $transactionService;

    /**
     * Constructor - inject ONE service only
     */
    public function __construct(CashTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display transactions list view.
     */
    public function index(): View
    {
        return view('cash-transactions.index');
    }

    /**
     * Display transaction creation form.
     */
    public function create(): View
    {
        return view('cash-transactions.create');
    }

    /**
     * Store a new cash transaction.
     */
    public function store(StoreCashTransactionRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $result = $this->transactionService->createTransaction(
            $request->validated(),
            $userId,
            $request
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * API: Get all transactions, optionally filtered by account.
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $accountId = $request->query('account_id');
        $transactions = $this->transactionService->getTransactions($accountId);
        return response()->json($transactions, 200);
    }

    /**
     * API: Get transaction details by ID.
     */
    public function apiShow(int $transactionId): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionDetails($transactionId);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        return response()->json($transaction, 200);
    }
}
