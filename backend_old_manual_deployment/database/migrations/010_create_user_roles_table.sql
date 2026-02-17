-- Migration: Create user_roles table
-- Description: Many-to-many relationship between users and roles
-- Version: 1.0
-- Date: 2026-02-05

CREATE TABLE user_roles (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_id, role_id)
);

CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX idx_user_roles_role_id ON user_roles(role_id);

COMMENT ON TABLE user_roles IS 'User-to-role assignments for RBAC';
COMMENT ON COLUMN user_roles.user_id IS 'User ID';
COMMENT ON COLUMN user_roles.role_id IS 'Role ID';
