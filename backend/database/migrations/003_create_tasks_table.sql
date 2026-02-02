-- Migration: Create tasks table
-- Description: Tasks for projects in the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create task status enum
CREATE TYPE task_status AS ENUM (
  'todo',
  'wip',
  'blocked',
  'done'
);

-- Create tasks table
CREATE TABLE tasks (
  id SERIAL PRIMARY KEY,
  project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE RESTRICT,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100),
  weight NUMERIC(5,2) NOT NULL CHECK (weight >= 0 AND weight <= 100),
  progress NUMERIC(5,2) NOT NULL DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
  status task_status NOT NULL DEFAULT 'todo',
  assigned_to INTEGER REFERENCES users(id) ON DELETE RESTRICT,
  start_date DATE,
  due_date DATE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT valid_task_date_range CHECK (due_date IS NULL OR start_date IS NULL OR due_date >= start_date)
);

-- Create indexes
CREATE INDEX idx_tasks_project_id ON tasks(project_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_tasks_created_at ON tasks(created_at);

-- Add comments for documentation
COMMENT ON TABLE tasks IS 'Tasks belonging to projects in the OPF-CD system';
COMMENT ON COLUMN tasks.project_id IS 'Parent project - task cannot exist without project';
COMMENT ON COLUMN tasks.name IS 'Task name or description';
COMMENT ON COLUMN tasks.category IS 'Task category for grouping';
COMMENT ON COLUMN tasks.weight IS 'Task weight for progress calculation - must be 0-100 (summation enforced in service layer)';
COMMENT ON COLUMN tasks.progress IS 'Task completion percentage - must be 0-100';
COMMENT ON COLUMN tasks.status IS 'Current task status in workflow';
COMMENT ON COLUMN tasks.assigned_to IS 'User assigned to this task - user cannot be deleted while assigned';
COMMENT ON COLUMN tasks.start_date IS 'Task start date (optional)';
COMMENT ON COLUMN tasks.due_date IS 'Task due date (optional) - must be >= start_date if both are set';
