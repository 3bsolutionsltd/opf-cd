<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/**
 * Cash Transaction Service
 * 
 * Single responsibility: Record cash inflows/outflows ONLY.
 * NO balance calculations - balances are computed elsewhere.
 * No decision logic, no business rules - just facts.
 * 
 * Returns facts only - NO business logic, NO frontend concerns.
 */
class CashTransactionService
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }
    /**
     * Get all cash transactions, optionally filtered by account.
     * 
     * @param int|null $accountId Optional account ID filter
     * @return array Array of transaction records
     */
    public function getTransactions(?int $accountId = null): array
    {
        $query = DB::table('cash_transactions')
            ->select(
                'cash_transactions.*',
                'accounts.name as account_name',
                'accounts.type as account_type'
            )
            ->leftJoin('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($accountId !== null) {
            $query->where('cash_transactions.account_id', $accountId);
        }

        $transactions = $query->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'account_id' => $transaction->account_id,
                    'account_name' => $transaction->account_name,
                    'account_type' => $transaction->account_type,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'currency' => $transaction->currency,
                    'source_type' => $transaction->source_type,
                    'source_id' => $transaction->source_id,
                    'transaction_date' => $transaction->transaction_date,
                    'created_at' => $transaction->created_at,
                ];
            })
            ->toArray();

        return $transactions;
    }

    /**
     * Get transaction details by ID.
     * 
     * @param int $transactionId
     * @return array|null Transaction record or null if not found
     */
    public function getTransactionDetails(int $transactionId): ?array
    {
        $transaction = DB::table('cash_transactions')
            ->select(
                'cash_transactions.*',
                'accounts.name as account_name',
                'accounts.type as account_type'
            )
            ->leftJoin('accounts', 'cash_transactions.account_id', '=', 'accounts.id')
            ->where('cash_transactions.id', $transactionId)
            ->first();

        if (!$transaction) {
            return null;
        }

        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $transaction->account_name,
            'account_type' => $transaction->account_type,
            'type' => $transaction->type,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'source_type' => $transaction->source_type,
            'source_id' => $transaction->source_id,
            'transaction_date' => $transaction->transaction_date,
            'created_at' => $transaction->created_at,
        ];
    }

    /**
     * Create a new cash transaction.
     * 
     * @param array $data Transaction data (account_id, type, amount, currency, source_type, source_id, transaction_date)
     * @param int $userId
     * @param Request|null $request
     * @return array ['success' => bool, 'message' => string, 'transaction_id' => int|null]
     */
    public function createTransaction(array $data, int $userId, ?Request $request = null): array
    {
        try {
            $transactionId = DB::table('cash_transactions')->insertGetId([
                'account_id' => $data['account_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'],
                'transaction_date' => $data['transaction_date'],
                'created_at' => now(),
            ]);

            // Log audit trail
            $this->auditService->logCreate(
                $userId,
                'cash_transactions',
                $transactionId,
                array_merge($data, ['id' => $transactionId]),
                $request
            );

            return [
                'success' => true,
                'message' => 'Transaction recorded successfully.',
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to record transaction: ' . $e->getMessage(),
                'transaction_id' => null,
            ];
        }
    }
}
