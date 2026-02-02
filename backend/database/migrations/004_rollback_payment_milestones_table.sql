-- Rollback: Drop payment_milestones table
-- Description: Removes payment_milestones table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_payment_milestones_created_at;
DROP INDEX IF EXISTS idx_payment_milestones_due_date;
DROP INDEX IF EXISTS idx_payment_milestones_status;
DROP INDEX IF EXISTS idx_payment_milestones_project_id;

-- Drop table
DROP TABLE IF EXISTS payment_milestones;

-- Drop enum type
DROP TYPE IF EXISTS payment_status;
