-- Rollback: Drop cash_transactions table
-- Description: Removes cash_transactions table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_cash_transactions_created_at;
DROP INDEX IF EXISTS idx_cash_transactions_source;
DROP INDEX IF EXISTS idx_cash_transactions_transaction_date;
DROP INDEX IF EXISTS idx_cash_transactions_type;
DROP INDEX IF EXISTS idx_cash_transactions_account_id;

-- Drop table
DROP TABLE IF EXISTS cash_transactions;

-- Drop enum type
DROP TYPE IF EXISTS transaction_type;
