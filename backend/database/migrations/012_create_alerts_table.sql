-- Migration: Create alerts table
-- Description: System alerts for project health, payments, and business risks
-- Version: 1.0
-- Date: 2026-02-14

-- Create alert severity enum
CREATE TYPE alert_severity AS ENUM (
  'info',
  'warning',
  'critical'
);

-- Create alert type enum
CREATE TYPE alert_type AS ENUM (
  'project_behind_schedule',
  'payment_gap_breach',
  'low_cash_runway',
  'expense_overdue',
  'opportunity_closing_soon'
);

-- Create alerts table
CREATE TABLE alerts (
  id SERIAL PRIMARY KEY,
  type alert_type NOT NULL,
  severity alert_severity NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INTEGER NOT NULL,
  message TEXT NOT NULL,
  is_dismissed BOOLEAN NOT NULL DEFAULT FALSE,
  dismissed_at TIMESTAMP WITH TIME ZONE,
  dismissed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_alerts_type ON alerts(type);
CREATE INDEX idx_alerts_severity ON alerts(severity);
CREATE INDEX idx_alerts_entity ON alerts(entity_type, entity_id);
CREATE INDEX idx_alerts_is_dismissed ON alerts(is_dismissed);
CREATE INDEX idx_alerts_created_at ON alerts(created_at);

-- Add comments for documentation
COMMENT ON TABLE alerts IS 'System-generated alerts for business risk detection';
COMMENT ON COLUMN alerts.type IS 'Alert type classification';
COMMENT ON COLUMN alerts.severity IS 'Alert severity level: info, warning, critical';
COMMENT ON COLUMN alerts.entity_type IS 'Entity this alert relates to (project, expense, opportunity, etc)';
COMMENT ON COLUMN alerts.entity_id IS 'ID of the related entity';
COMMENT ON COLUMN alerts.message IS 'Human-readable alert message';
COMMENT ON COLUMN alerts.is_dismissed IS 'Whether user has acknowledged/dismissed this alert';
COMMENT ON COLUMN alerts.dismissed_at IS 'Timestamp when alert was dismissed';
COMMENT ON COLUMN alerts.dismissed_by IS 'User who dismissed the alert';

