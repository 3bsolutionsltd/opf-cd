-- Rollback: Drop audit_logs table
-- Purpose: Reverse migration 013_create_audit_logs_table.sql

DROP TABLE IF EXISTS audit_logs;
DROP TYPE IF EXISTS audit_action;
