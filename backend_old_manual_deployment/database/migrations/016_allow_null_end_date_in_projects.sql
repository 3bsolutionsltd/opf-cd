-- Migration: Allow NULL end_date in projects table
-- Purpose: Enable projects to be created without an end date (to be set manually later)
-- Date: 2026-02-20

-- Remove NOT NULL constraint from end_date
ALTER TABLE projects 
ALTER COLUMN end_date DROP NOT NULL;

-- Drop the existing check constraint
ALTER TABLE projects 
DROP CONSTRAINT IF EXISTS valid_date_range;

-- Add new check constraint that allows NULL end dates
ALTER TABLE projects 
ADD CONSTRAINT valid_date_range 
CHECK (end_date IS NULL OR end_date >= start_date);

-- Update comment for documentation
COMMENT ON COLUMN projects.end_date IS 'Project end date - must be >= start_date if set, NULL if not yet determined';
