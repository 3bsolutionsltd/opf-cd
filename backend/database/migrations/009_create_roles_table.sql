-- Migration: Create roles table
-- Description: User roles for RBAC (Admin, Project Manager, Finance, Sales, Viewer)
-- Version: 1.0
-- Date: 2026-02-05

CREATE TABLE roles (
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_roles_name ON roles(name);

COMMENT ON TABLE roles IS 'User roles for role-based access control';
COMMENT ON COLUMN roles.name IS 'Unique role name (Admin, Project Manager, Finance, Sales, Viewer)';
