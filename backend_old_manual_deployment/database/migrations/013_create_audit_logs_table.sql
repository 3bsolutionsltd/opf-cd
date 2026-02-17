-- Migration: Create audit_logs table
-- Purpose: Track all data changes for compliance and debugging
-- Source: docs/PRODUCTION_ROADMAP.md Sprint 4

CREATE TYPE audit_action AS ENUM ('create', 'update', 'delete');

CREATE TABLE audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    action audit_action NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INTEGER NOT NULL,
    changes JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for efficient querying
CREATE INDEX idx_audit_user ON audit_logs(user_id);
CREATE INDEX idx_audit_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_action ON audit_logs(action);
CREATE INDEX idx_audit_created ON audit_logs(created_at DESC);

-- Composite index for common query pattern (entity + time)
CREATE INDEX idx_audit_entity_time ON audit_logs(entity_type, entity_id, created_at DESC);

COMMENT ON TABLE audit_logs IS 'Immutable audit trail of all data modifications';
COMMENT ON COLUMN audit_logs.changes IS 'JSONB containing before/after values for updates, full record for create/delete';
COMMENT ON COLUMN audit_logs.entity_type IS 'Table name: projects, tasks, expenses, etc.';
COMMENT ON COLUMN audit_logs.entity_id IS 'Primary key of modified record';
