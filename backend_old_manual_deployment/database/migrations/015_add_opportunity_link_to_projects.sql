-- Migration: Add opportunity link to projects table
-- Purpose: Enable tracking of projects created from won opportunities
-- Date: 2026-02-20

-- Add opportunity_id column to projects table
ALTER TABLE projects 
ADD COLUMN opportunity_id INTEGER;

-- Add foreign key constraint
ALTER TABLE projects 
ADD CONSTRAINT fk_projects_opportunity 
FOREIGN KEY (opportunity_id) 
REFERENCES opportunities(id) 
ON DELETE SET NULL;

-- Create index for performance
CREATE INDEX idx_projects_opportunity_id ON projects(opportunity_id);

-- Add comment for documentation
COMMENT ON COLUMN projects.opportunity_id IS 'Foreign key to the opportunity that spawned this project. NULL if project was not created from an opportunity.';
