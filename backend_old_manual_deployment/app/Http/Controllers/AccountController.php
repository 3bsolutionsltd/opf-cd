<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Services\AccountManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Account Controller
 * 
 * Thin pass-through controller - NO transformations, NO calculations.
 * Calls ONE service (AccountManagementService).
 */
class AccountController extends Controller
{
    /**
     * Injected service
     */
    private AccountManagementService $accountService;

    /**
     * Constructor - inject ONE service only
     */
    public function __construct(AccountManagementService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Display accounts list view.
     */
    public function index(): View
    {
        return view('accounts.index');
    }

    /**
     * Display account creation form.
     */
    public function create(): View
    {
        return view('accounts.create');
    }

    /**
     * Store a new account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->accountService->createAccount(
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
     * Display account edit form.
     */
    public function edit(int $accountId): View
    {
        return view('accounts.edit', ['accountId' => $accountId]);
    }

    /**
     * Update an existing account.
     */
    public function update(UpdateAccountRequest $request, int $accountId): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->accountService->updateAccount(
            $accountId,
            $request->validated(),
            $userId,
            $request
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * Delete an account.
     */
    public function destroy(\Illuminate\Http\Request $request, int $accountId): JsonResponse
    {
        $userId = $request->get('authenticated_user_id');
        $result = $this->accountService->deleteAccount($accountId, $userId, $request);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    /**
     * API: Get all accounts.
     */
    public function apiIndex(): JsonResponse
    {
        $accounts = $this->accountService->getAccounts();
        return response()->json($accounts, 200);
    }

    /**
     * API: Get account details by ID.
     */
    public function apiShow(int $accountId): JsonResponse
    {
        $account = $this->accountService->getAccountDetails($accountId);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        return response()->json($account, 200);
    }
}
