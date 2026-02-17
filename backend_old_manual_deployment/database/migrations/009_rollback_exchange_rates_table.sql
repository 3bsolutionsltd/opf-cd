-- Rollback: Drop exchange_rates table
-- Description: Removes exchange_rates table
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_exchange_rates_created_at;
DROP INDEX IF EXISTS idx_exchange_rates_effective_date;

-- Drop table
DROP TABLE IF EXISTS exchange_rates;
