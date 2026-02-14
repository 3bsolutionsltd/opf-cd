<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;
use InvalidArgumentException;

/**
 * Receive Project Payment Service
 * 
 * Single responsibility: Record receipt of a project payment.
 * 
 * Domain Rules:
 * - payment_milestones = expected/contractual payments (not cash)
 * - cash_transactions = actual cash movement (source of truth for dashboards)
 * - Milestone status transition: pending → invoiced → paid (IMMUTABLE once paid)
 * - No automatic inference: cash must be explicitly recorded
 * - All operations atomic and auditable
 * 
 * This service does NOT:
 * - Update dashboards (they read from cash_transactions)
 * - Compute derived values
 * - Auto-run on milestone creation
 * - Mix UI concerns
 * 
 * Source: docs/_truth.md, docs/copilot_rules.md
 */
class ReceiveProjectPaymentService
{
    /**
     * Record receipt of a project payment.
     * 
     * Creates a cash_transactions record and marks the milestone as paid.
     * All operations are performed atomically within a single database transaction.
     * Operation is idempotent - will fail safely if payment already recorded.
     * 
     * @param int $milestoneId The payment milestone being fulfilled
     * @param int $accountId The account receiving the funds
     * @param string $transactionDate Date payment received (YYYY-MM-DD format)
     * 
     * @return array ['success' => true, 'message' => string, 'transaction_id' => int]
     * 
     * @throws InvalidArgumentException If validation fails
     * @throws Exception If database operation fails
     */
    public function receive(int $milestoneId, int $accountId, string $transactionDate): array
    {
        try {
            // Begin atomic transaction - ensures all-or-nothing execution
            return DB::transaction(function () use ($milestoneId, $accountId, $transactionDate) {
                
                // ===================================================================
                // VALIDATION PHASE - Enforce all domain invariants before ANY writes
                // ===================================================================
                
                // 1. Verify milestone exists
                $milestone = DB::table('payment_milestones')
                    ->where('id', $milestoneId)
                    ->lockForUpdate() // Pessimistic lock prevents concurrent modifications
                    ->first();
                
                if (!$milestone) {
                    throw new InvalidArgumentException(
                        "Payment milestone #{$milestoneId} does not exist."
                    );
                }
                
                // 2. Enforce immutability - paid milestones cannot be re-paid
                //    This is the idempotency check: prevents duplicate cash records
                if ($milestone->status === 'paid') {
                    throw new InvalidArgumentException(
                        "Payment milestone #{$milestoneId} is already marked as paid. Cannot record payment twice."
                    );
                }
                
                // 3. Verify account exists
                $account = DB::table('accounts')
                    ->where('id', $accountId)
                    ->first();
                
                if (!$account) {
                    throw new InvalidArgumentException(
                        "Account #{$accountId} does not exist."
                    );
                }
                
                // 4. Enforce currency integrity - prevent cross-currency errors
                //    milestone.currency MUST match account.currency
                if ($milestone->currency !== $account->currency) {
                    throw new InvalidArgumentException(
                        "Currency mismatch: Milestone is {$milestone->currency} but account is {$account->currency}. Cannot record cross-currency payment."
                    );
                }
                
                // 5. Validate transaction date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
                    throw new InvalidArgumentException(
                        "Invalid transaction date format. Expected YYYY-MM-DD, got: {$transactionDate}"
                    );
                }
                
                // 6. Additional safety check: Verify no duplicate cash_transaction exists
                //    (Should be impossible due to status check, but defense in depth)
                $existingTransaction = DB::table('cash_transactions')
                    ->where('source_type', 'payment_milestone')
                    ->where('source_id', $milestoneId)
                    ->exists();
                
                if ($existingTransaction) {
                    throw new InvalidArgumentException(
                        "Cash transaction already exists for milestone #{$milestoneId}. This should not happen (data integrity issue)."
                    );
                }
                
                // ===================================================================
                // WRITE PHASE - All validations passed, perform atomic state change
                // ===================================================================
                
                // 7. Create cash_transactions record (append-only, immutable)
                //    This is the SOURCE OF TRUTH for all cash-based dashboards
                $transactionId = DB::table('cash_transactions')->insertGetId([
                    'account_id' => $accountId,
                    'type' => 'inflow', // Payment receipt is always an inflow
                    'amount' => $milestone->amount, // Exact amount from milestone (no inference)
                    'currency' => $milestone->currency, // Already validated to match account
                    'source_type' => 'payment_milestone', // Links transaction to its origin
                    'source_id' => $milestoneId, // Foreign key to payment_milestones.id
                    'transaction_date' => $transactionDate, // Actual date cash received
                    'created_at' => now(), // Audit trail: when record was created
                ]);
                
                // 8. Update milestone status to 'paid' (IMMUTABLE transition)
                //    Once set to 'paid', this record can never be edited again
                $updated = DB::table('payment_milestones')
                    ->where('id', $milestoneId)
                    ->update([
                        'status' => 'paid',
                        'updated_at' => now(),
                    ]);
                
                // Sanity check: ensure update succeeded
                if ($updated !== 1) {
                    throw new Exception(
                        "Failed to update milestone #{$milestoneId} status. Expected 1 row affected, got {$updated}."
                    );
                }
                
                // ===================================================================
                // SUCCESS - Return facts only, no derived data
                // ===================================================================
                
                return [
                    'success' => true,
                    'message' => "Payment received: {$milestone->currency} {$milestone->amount} recorded for milestone '{$milestone->name}' (ID: {$milestoneId})",
                    'transaction_id' => $transactionId,
                    'milestone_id' => $milestoneId,
                    'account_id' => $accountId,
                    'amount' => (float) $milestone->amount,
                    'currency' => $milestone->currency,
                    'transaction_date' => $transactionDate,
                ];
                
                // Transaction auto-commits if we reach here without exception
                // Transaction auto-rolls back if ANY exception thrown above
            });
            
        } catch (InvalidArgumentException $e) {
            // Business rule violations - return as structured error
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'transaction_id' => null,
            ];
            
        } catch (Exception $e) {
            // Database or system errors - return as structured error
            return [
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
                'transaction_id' => null,
            ];
        }
    }
    
    /**
     * Check if a milestone has already been paid (read-only query).
     * 
     * Useful for UI validation before attempting payment recording.
     * 
     * @param int $milestoneId
     * @return bool True if milestone status is 'paid'
     */
    public function isPaid(int $milestoneId): bool
    {
        $milestone = DB::table('payment_milestones')
            ->where('id', $milestoneId)
            ->value('status');
        
        return $milestone === 'paid';
    }
    
    /**
     * Get payment receipt details (read-only query).
     * 
     * Returns the cash transaction record associated with a paid milestone.
     * Returns null if milestone not yet paid.
     * 
     * @param int $milestoneId
     * @return array|null Transaction details or null if not paid
     */
    public function getPaymentReceipt(int $milestoneId): ?array
    {
        $transaction = DB::table('cash_transactions')
            ->where('source_type', 'payment_milestone')
            ->where('source_id', $milestoneId)
            ->first();
        
        if (!$transaction) {
            return null;
        }
        
        return [
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'transaction_date' => $transaction->transaction_date,
            'created_at' => $transaction->created_at,
        ];
    }
}
