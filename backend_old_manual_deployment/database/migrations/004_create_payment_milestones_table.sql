-- Migration: Create payment_milestones table
-- Description: Payment milestones for projects in the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create payment status enum
CREATE TYPE payment_status AS ENUM (
  'pending',
  'invoiced',
  'paid'
);

-- Create payment_milestones table
CREATE TABLE payment_milestones (
  id SERIAL PRIMARY KEY,
  project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE RESTRICT,
  name VARCHAR(255) NOT NULL,
  amount NUMERIC(15,2) NOT NULL CHECK (amount >= 0),
  currency currency_code NOT NULL,
  status payment_status NOT NULL DEFAULT 'pending',
  due_date DATE NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_payment_milestones_project_id ON payment_milestones(project_id);
CREATE INDEX idx_payment_milestones_status ON payment_milestones(status);
CREATE INDEX idx_payment_milestones_due_date ON payment_milestones(due_date);
CREATE INDEX idx_payment_milestones_created_at ON payment_milestones(created_at);

-- Add comments for documentation
COMMENT ON TABLE payment_milestones IS 'Payment milestones for projects - financial records are immutable once paid';
COMMENT ON COLUMN payment_milestones.project_id IS 'Parent project - payment records cannot be cascade deleted';
COMMENT ON COLUMN payment_milestones.name IS 'Milestone name or invoice reference';
COMMENT ON COLUMN payment_milestones.amount IS 'Milestone payment amount - must be non-negative';
COMMENT ON COLUMN payment_milestones.currency IS 'Currency code for the payment amount';
COMMENT ON COLUMN payment_milestones.status IS 'Payment status: pending → invoiced → paid';
COMMENT ON COLUMN payment_milestones.due_date IS 'Payment due date';
