-- Migration: Create users table
-- Description: Internal users for the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create role enum
CREATE TYPE user_role AS ENUM (
  'admin',
  'project_manager',
  'finance',
  'sales',
  'viewer'
);

-- Create users table
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role user_role NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT true,
  last_login_at TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Add comment for documentation
COMMENT ON TABLE users IS 'Internal users for the OPF-CD system';
COMMENT ON COLUMN users.email IS 'Unique email address used for authentication';
COMMENT ON COLUMN users.password_hash IS 'Hashed password - never store plain text';
COMMENT ON COLUMN users.role IS 'User role determines system permissions';
COMMENT ON COLUMN users.is_active IS 'Soft delete flag - false means user is disabled';
COMMENT ON COLUMN users.last_login_at IS 'Timestamp of most recent successful login';
