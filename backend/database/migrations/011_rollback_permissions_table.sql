-- Rollback: Drop permissions table
DROP TABLE IF EXISTS permissions CASCADE;
DROP TYPE IF EXISTS action_type;
DROP TYPE IF EXISTS resource_type;
