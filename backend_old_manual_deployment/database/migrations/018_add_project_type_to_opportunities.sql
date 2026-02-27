-- Add project_type field to opportunities table to link with templates
ALTER TABLE opportunities ADD COLUMN IF NOT EXISTS project_type VARCHAR(100);
ALTER TABLE opportunities ADD COLUMN IF NOT EXISTS auto_apply_template BOOLEAN DEFAULT false;

-- Add foreign key to project_templates if template selected
ALTER TABLE opportunities ADD COLUMN IF NOT EXISTS suggested_template_id INT REFERENCES project_templates(id) ON DELETE SET NULL;

-- Create index for template lookups
CREATE INDEX IF NOT EXISTS idx_opportunities_project_type ON opportunities(project_type);
CREATE INDEX IF NOT EXISTS idx_opportunities_template ON opportunities(suggested_template_id);

-- Add comment to document the project types
COMMENT ON COLUMN opportunities.project_type IS 'Project type for template selection: Web App, Mobile App, E-Commerce, Integration, Maintenance';
COMMENT ON COLUMN opportunities.auto_apply_template IS 'Whether to automatically apply template when opportunity is won';
