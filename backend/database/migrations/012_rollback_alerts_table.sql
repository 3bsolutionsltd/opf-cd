-- Rollback: Drop alerts table
-- Description: Rollback for create_alerts_table migration
-- Version: 1.0
-- Date: 2026-02-14

DROP TABLE IF EXISTS alerts;
DROP TYPE IF EXISTS alert_type;
DROP TYPE IF EXISTS alert_severity;
