-- Migration: Create permissions table
-- Description: Fine-grained permissions for role-based access control
-- Version: 1.0
-- Date: 2026-02-05

-- Resource types (entities that can be acted upon)
CREATE TYPE resource_type AS ENUM (
  'projects',
  'tasks',
  'milestones',
  'expenses',
  'accounts',
  'cash_transactions',
  'opportunities',
  'users',
  'roles',
  'permissions',
  'dashboards'
);

-- Action types (operations that can be performed)
CREATE TYPE action_type AS ENUM (
  'view',
  'create',
  'edit',
  'delete',
  'manage'
);

CREATE TABLE permissions (
  id SERIAL PRIMARY KEY,
  role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  resource resource_type NOT NULL,
  action action_type NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(role_id, resource, action)
);

CREATE INDEX idx_permissions_role_id ON permissions(role_id);
CREATE INDEX idx_permissions_resource ON permissions(resource);
CREATE INDEX idx_permissions_action ON permissions(action);

COMMENT ON TABLE permissions IS 'Fine-grained access permissions for roles';
COMMENT ON COLUMN permissions.role_id IS 'Role this permission applies to';
COMMENT ON COLUMN permissions.resource IS 'Resource type (entity)';
COMMENT ON COLUMN permissions.action IS 'Action allowed on resource';
