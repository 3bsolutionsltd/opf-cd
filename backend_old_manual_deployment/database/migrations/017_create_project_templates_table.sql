-- Migration: Create project templates tables
-- Description: Project templates for standardized project creation
-- Version: 1.0
-- Date: 2026-02-27

-- Create template category enum
CREATE TYPE template_category AS ENUM (
  'Web App',
  'Mobile App',
  'E-Commerce',
  'Integration',
  'Maintenance'
);

-- Create project_templates table
CREATE TABLE project_templates (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  description TEXT,
  category template_category NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT true,
  task_count INTEGER NOT NULL DEFAULT 0,
  average_duration_days INTEGER,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for project_templates
CREATE INDEX idx_templates_category ON project_templates(category);
CREATE INDEX idx_templates_active ON project_templates(is_active);
CREATE INDEX idx_templates_created_at ON project_templates(created_at);

-- Create project_template_tasks table
CREATE TABLE project_template_tasks (
  id SERIAL PRIMARY KEY,
  project_template_id INTEGER NOT NULL REFERENCES project_templates(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  weight NUMERIC(5,2) NOT NULL DEFAULT 0 CHECK (weight >= 0 AND weight <= 100),
  phase_number INTEGER NOT NULL,
  estimated_duration_days INTEGER,
  dependencies VARCHAR(255),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for project_template_tasks
CREATE INDEX idx_template_tasks_template ON project_template_tasks(project_template_id);
CREATE INDEX idx_template_tasks_phase ON project_template_tasks(phase_number);
CREATE INDEX idx_template_tasks_created_at ON project_template_tasks(created_at);

-- Add documentation comments
COMMENT ON TABLE project_templates IS 'Reusable project templates for standardized project creation';
COMMENT ON COLUMN project_templates.name IS 'Unique template name';
COMMENT ON COLUMN project_templates.description IS 'Template description and intended use';
COMMENT ON COLUMN project_templates.category IS 'Template category for grouping and filtering';
COMMENT ON COLUMN project_templates.is_active IS 'Whether template is available for use';
COMMENT ON COLUMN project_templates.task_count IS 'Number of tasks in template - denormalized for quick display';
COMMENT ON COLUMN project_templates.average_duration_days IS 'Typical project duration for this template';

COMMENT ON TABLE project_template_tasks IS 'Task definitions within project templates';
COMMENT ON COLUMN project_template_tasks.project_template_id IS 'Parent template - task cannot exist without template';
COMMENT ON COLUMN project_template_tasks.name IS 'Task name or title';
COMMENT ON COLUMN project_template_tasks.description IS 'Task description and expected deliverables';
COMMENT ON COLUMN project_template_tasks.weight IS 'Task weight for progress calculation - must be 0-100 (summation enforced in service layer)';
COMMENT ON COLUMN project_template_tasks.phase_number IS 'Display order and phase sequencing';
COMMENT ON COLUMN project_template_tasks.estimated_duration_days IS 'Typical duration for completing this task';
COMMENT ON COLUMN project_template_tasks.dependencies IS 'Comma-separated phase numbers this task depends on';
