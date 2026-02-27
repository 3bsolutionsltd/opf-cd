-- Create project_templates table for template management
CREATE TABLE IF NOT EXISTS project_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100) NOT NULL, -- Web App, Mobile App, E-Commerce, Integration, Maintenance
    is_active BOOLEAN DEFAULT true,
    task_count INT DEFAULT 0, -- Denormalized for quick display
    average_duration_days INT, -- Typical project duration for this template
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_templates_category ON project_templates(category);
CREATE INDEX idx_templates_active ON project_templates(is_active);

-- Create project_template_tasks table for individual tasks in templates
CREATE TABLE IF NOT EXISTS project_template_tasks (
    id SERIAL PRIMARY KEY,
    project_template_id INT NOT NULL REFERENCES project_templates(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    weight INT NOT NULL DEFAULT 0, -- Percentage weight of total project (must sum to 100)
    phase_number INT NOT NULL, -- Display order
    estimated_duration_days INT, -- Typical duration for this phase
    dependencies VARCHAR(255), -- Comma-separated phase numbers this depends on
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT weight_range CHECK (weight >= 0 AND weight <= 100)
);

CREATE INDEX idx_template_tasks_template ON project_template_tasks(project_template_id);
CREATE INDEX idx_template_tasks_phase ON project_template_tasks(phase_number);
