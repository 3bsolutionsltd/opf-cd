<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

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
     * @return array ['success' => bool, 'message' => string, 'account_id' => int|null]
     */
    public function createAccount(array $data): array
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
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateAccount(int $accountId, array $data): array
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
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteAccount(int $accountId): array
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

        try {
            DB::table('accounts')
                ->where('id', $accountId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Account deleted successfully.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage(),
            ];
        }
    }
}
