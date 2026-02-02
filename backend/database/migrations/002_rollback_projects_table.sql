-- Rollback: Drop projects table
-- Description: Removes projects table and associated enum types
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_projects_created_at;
DROP INDEX IF EXISTS idx_projects_end_date;
DROP INDEX IF EXISTS idx_projects_start_date;
DROP INDEX IF EXISTS idx_projects_client;
DROP INDEX IF EXISTS idx_projects_project_lead_id;
DROP INDEX IF EXISTS idx_projects_status;

-- Drop table
DROP TABLE IF EXISTS projects;

-- Drop enum types
DROP TYPE IF EXISTS project_status;
DROP TYPE IF EXISTS currency_code;
