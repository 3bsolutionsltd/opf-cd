-- Rollback: Remove currency column from opportunities table
-- Description: Rollback for adding currency field
-- Version: 1.1
-- Date: 2026-02-20

-- Drop the index
DROP INDEX IF EXISTS idx_opportunities_currency;

-- Remove the currency column
ALTER TABLE opportunities DROP COLUMN IF EXISTS currency;
