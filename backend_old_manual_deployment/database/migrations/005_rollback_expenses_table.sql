-- Rollback: Drop expenses table
-- Description: Removes expenses table and associated enum types
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_expenses_created_at;
DROP INDEX IF EXISTS idx_expenses_due_date;
DROP INDEX IF EXISTS idx_expenses_project_id;
DROP INDEX IF EXISTS idx_expenses_status;
DROP INDEX IF EXISTS idx_expenses_type;

-- Drop table
DROP TABLE IF EXISTS expenses;

-- Drop enum types
DROP TYPE IF EXISTS expense_status;
DROP TYPE IF EXISTS expense_frequency;
DROP TYPE IF EXISTS expense_type;
