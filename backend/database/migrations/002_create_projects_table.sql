-- Migration: Create projects table
-- Description: Projects for the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create currency enum
CREATE TYPE currency_code AS ENUM (
  'UGX',
  'USD'
);

-- Create project status enum
CREATE TYPE project_status AS ENUM (
  'planned',
  'active',
  'on_hold',
  'completed',
  'cancelled'
);

-- Create projects table
CREATE TABLE projects (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  client VARCHAR(255) NOT NULL,
  contract_value NUMERIC(15,2) NOT NULL CHECK (contract_value >= 0),
  contract_currency currency_code NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status project_status NOT NULL DEFAULT 'planned',
  project_lead_id INTEGER REFERENCES users(id) ON DELETE RESTRICT,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT valid_date_range CHECK (end_date >= start_date)
);

-- Create indexes
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_project_lead_id ON projects(project_lead_id);
CREATE INDEX idx_projects_client ON projects(client);
CREATE INDEX idx_projects_start_date ON projects(start_date);
CREATE INDEX idx_projects_end_date ON projects(end_date);
CREATE INDEX idx_projects_created_at ON projects(created_at);

-- Add comments for documentation
COMMENT ON TABLE projects IS 'Projects tracked in the OPF-CD system';
COMMENT ON COLUMN projects.name IS 'Project name or title';
COMMENT ON COLUMN projects.client IS 'Client or customer name';
COMMENT ON COLUMN projects.contract_value IS 'Total contract value - must be non-negative';
COMMENT ON COLUMN projects.contract_currency IS 'Currency code for the contract value';
COMMENT ON COLUMN projects.start_date IS 'Project start date';
COMMENT ON COLUMN projects.end_date IS 'Project end date - must be >= start_date';
COMMENT ON COLUMN projects.status IS 'Current project status in lifecycle';
COMMENT ON COLUMN projects.project_lead_id IS 'User ID of the project lead - user cannot be deleted while assigned';
