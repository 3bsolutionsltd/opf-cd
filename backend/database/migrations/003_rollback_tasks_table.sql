-- Rollback: Drop tasks table
-- Description: Removes tasks table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_tasks_created_at;
DROP INDEX IF EXISTS idx_tasks_due_date;
DROP INDEX IF EXISTS idx_tasks_assigned_to;
DROP INDEX IF EXISTS idx_tasks_status;
DROP INDEX IF EXISTS idx_tasks_project_id;

-- Drop table
DROP TABLE IF EXISTS tasks;

-- Drop enum type
DROP TYPE IF EXISTS task_status;
