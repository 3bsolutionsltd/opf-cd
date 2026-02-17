-- Rollback: Drop accounts table
-- Description: Removes accounts table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_accounts_created_at;
DROP INDEX IF EXISTS idx_accounts_currency;
DROP INDEX IF EXISTS idx_accounts_type;

-- Drop table
DROP TABLE IF EXISTS accounts;

-- Drop enum type
DROP TYPE IF EXISTS account_type;
