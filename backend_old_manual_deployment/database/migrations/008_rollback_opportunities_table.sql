-- Rollback: Drop opportunities table
-- Description: Removes opportunities table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_opportunities_created_at;
DROP INDEX IF EXISTS idx_opportunities_expected_close_date;
DROP INDEX IF EXISTS idx_opportunities_owner;
DROP INDEX IF EXISTS idx_opportunities_stage;

-- Drop table
DROP TABLE IF EXISTS opportunities;

-- Drop enum type
DROP TYPE IF EXISTS opportunity_stage;
