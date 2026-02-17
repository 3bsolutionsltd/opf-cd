-- Migration: Create opportunities table
-- Description: Sales opportunities for the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create opportunity stage enum
CREATE TYPE opportunity_stage AS ENUM (
  'lead',
  'qualified',
  'proposal',
  'negotiation',
  'won',
  'lost'
);

-- Create opportunities table
CREATE TABLE opportunities (
  id SERIAL PRIMARY KEY,
  client VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL,
  estimated_value NUMERIC(15,2) NOT NULL CHECK (estimated_value >= 0),
  probability NUMERIC(5,2) NOT NULL CHECK (probability >= 0 AND probability <= 100),
  stage opportunity_stage NOT NULL DEFAULT 'lead',
  source VARCHAR(100) NOT NULL,
  owner INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  expected_close_date DATE NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_opportunities_stage ON opportunities(stage);
CREATE INDEX idx_opportunities_owner ON opportunities(owner);
CREATE INDEX idx_opportunities_expected_close_date ON opportunities(expected_close_date);
CREATE INDEX idx_opportunities_created_at ON opportunities(created_at);

-- Add comments for documentation
COMMENT ON TABLE opportunities IS 'Sales opportunities for pipeline management';
COMMENT ON COLUMN opportunities.client IS 'Client or company name';
COMMENT ON COLUMN opportunities.description IS 'Opportunity description';
COMMENT ON COLUMN opportunities.estimated_value IS 'Estimated deal value - must be non-negative';
COMMENT ON COLUMN opportunities.probability IS 'Win probability percentage (0-100) - used in weighted pipeline calculation';
COMMENT ON COLUMN opportunities.stage IS 'Current stage in sales funnel';
COMMENT ON COLUMN opportunities.source IS 'Opportunity source or origin (e.g., referral, website, cold call)';
COMMENT ON COLUMN opportunities.owner IS 'User responsible for this opportunity - user cannot be deleted while assigned';
COMMENT ON COLUMN opportunities.expected_close_date IS 'Expected closing date for forecasting';
