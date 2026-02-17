<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Account Management Service
 * 
 * Single responsibility: Handle CRUD operations for financial accounts.
 * No decision logic, no business rules - just facts.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class AccountManagementService
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Get all accounts ordered by created date.
     * 
     * @return array Array of account records
     */
    public function getAccounts(): array
    {
        $accounts = DB::table('accounts')
            ->select('*')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type,
                    'currency' => $account->currency,
                    'opening_balance' => (float) $account->opening_balance,
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at,
                ];
            })
            ->toArray();

        return $accounts;
    }

    /**
     * Get account details by ID.
     * 
     * @param int $accountId
     * @return array|null Account record or null if not found
     */
    public function getAccountDetails(int $accountId): ?array
    {
        $account = DB::table('accounts')
            ->where('id', $accountId)
            ->first();

        if (!$account) {
            return null;
        }

        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type,
            'currency' => $account->currency,
            'opening_balance' => (float) $account->opening_balance,
            'created_at' => $account->created_at,
            'updated_at' => $account->updated_at,
        ];
    }

    /**
     * Create a new account.
     * 
     * @param array $data Account data (name, type, currency, opening_balance)
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string, 'account_id' => int|null]
     */
    public function createAccount(array $data, int $userId, ?Request $request = null): array
    {
        try {
            $accountId = DB::table('accounts')->insertGetId([
                'name' => $data['name'],
                'type' => $data['type'],
                'currency' => $data['currency'],
                'opening_balance' => $data['opening_balance'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'accounts',
                $accountId,
                array_merge($data, ['id' => $accountId]),
                $request
            );

            return [
                'success' => true,
                'message' => 'Account created successfully.',
                'account_id' => $accountId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create account: ' . $e->getMessage(),
                'account_id' => null,
            ];
        }
    }

    /**
     * Update an existing account.
     * 
     * @param int $accountId
     * @param array $data Updated account data
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateAccount(int $accountId, array $data, int $userId, ?Request $request = null): array
    {
        // Check if account exists
        $account = DB::table('accounts')
            ->where('id', $accountId)
            ->first();

        if (!$account) {
            return [
                'success' => false,
                'message' => 'Account not found.',
            ];
        }

        // Store before state for audit log
        $before = (array) $account;

        try {
            $updateData = array_filter([
                'name' => $data['name'] ?? null,
                'type' => $data['type'] ?? null,
                'currency' => $data['currency'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? null,
                'updated_at' => now(),
            ], function ($value) {
                return $value !== null;
            });

            DB::table('accounts')
                ->where('id', $accountId)
                ->update($updateData);

            // Get after state for audit log
            $after = (array) DB::table('accounts')->where('id', $accountId)->first();

            // Log audit trail
            $this->auditService->logUpdate(
                $userId,
                'accounts',
                $accountId,
                $before,
                $after,
                $request
            );

            return [
                'success' => true,
                'message' => 'Account updated successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update account: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete an account.
     * 
     * @param int $accountId
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteAccount(int $accountId, int $userId, ?Request $request = null): array
    {
        // Check if account exists
        $account = DB::table('accounts')
            ->where('id', $accountId)
            ->first();

        if (!$account) {
            return [
                'success' => false,
                'message' => 'Account not found.',
            ];
        }

        // Store final state for audit log
        $deletedData = (array) $account;

        try {
            DB::table('accounts')
                ->where('id', $accountId)
                ->delete();

            // Log audit trail
            $this->auditService->logDelete(
                $userId,
                'accounts',
                $accountId,
                $deletedData,
                $request
            );

            return [
                'success' => true,
                'message' => 'Account deleted successfully.',
            ];
        } catch (\Exception $e) {
            // Check if it's a foreign key constraint violation
            if (str_contains($e->getMessage(), 'Foreign key violation') || 
                str_contains($e->getMessage(), 'foreign key constraint') ||
                str_contains($e->getMessage(), '23503')) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete account because it has associated transactions. Please delete all transactions first.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete account. Please try again or contact support.',
            ];
        }
    }
}
