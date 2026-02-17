-- Rollback: Drop users table
-- Description: Removes users table and associated enum type
-- Version: 1.0
-- Date: 2026-02-02

-- Drop indexes (will be automatically dropped with table, but explicit for clarity)
DROP INDEX IF EXISTS idx_users_created_at;
DROP INDEX IF EXISTS idx_users_is_active;
DROP INDEX IF EXISTS idx_users_role;
DROP INDEX IF EXISTS idx_users_email;

-- Drop table
DROP TABLE IF EXISTS users;

-- Drop enum type
DROP TYPE IF EXISTS user_role;
