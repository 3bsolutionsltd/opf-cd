-- SQLite Migration: Create all tables
-- Converted from PostgreSQL migrations for OPF-CD system
-- Date: 2026-02-02

-- ===== USERS TABLE =====
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL CHECK (role IN ('admin', 'project_manager', 'finance', 'sales', 'viewer')),
  is_active INTEGER NOT NULL DEFAULT 1,
  last_login_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_active ON users(is_active);
CREATE INDEX idx_users_created_at ON users(created_at);

-- ===== PROJECTS TABLE =====
CREATE TABLE projects (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  client TEXT NOT NULL,
  contract_value REAL NOT NULL CHECK (contract_value >= 0),
  contract_currency TEXT NOT NULL CHECK (contract_currency IN ('UGX', 'USD')),
  start_date TEXT NOT NULL,
  end_date TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'planned' CHECK (status IN ('planned', 'active', 'on_hold', 'completed', 'cancelled')),
  project_lead_id INTEGER REFERENCES users(id) ON DELETE RESTRICT,
  opportunity_id INTEGER REFERENCES opportunities(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  CHECK (end_date >= start_date)
);

CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_project_lead_id ON projects(project_lead_id);
CREATE INDEX idx_projects_client ON projects(client);
CREATE INDEX idx_projects_start_date ON projects(start_date);
CREATE INDEX idx_projects_end_date ON projects(end_date);
CREATE INDEX idx_projects_created_at ON projects(created_at);
CREATE INDEX idx_projects_opportunity_id ON projects(opportunity_id);

-- ===== TASKS TABLE =====
CREATE TABLE tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE RESTRICT,
  name TEXT NOT NULL,
  category TEXT,
  weight REAL NOT NULL CHECK (weight >= 0 AND weight <= 100),
  progress REAL NOT NULL DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
  status TEXT NOT NULL DEFAULT 'todo' CHECK (status IN ('todo', 'wip', 'blocked', 'done')),
  assigned_to INTEGER REFERENCES users(id) ON DELETE RESTRICT,
  start_date TEXT,
  due_date TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  CHECK (due_date IS NULL OR start_date IS NULL OR due_date >= start_date)
);

CREATE INDEX idx_tasks_project_id ON tasks(project_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_tasks_created_at ON tasks(created_at);

-- ===== PAYMENT MILESTONES TABLE =====
CREATE TABLE payment_milestones (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(id) ON DELETE RESTRICT,
  name TEXT NOT NULL,
  amount REAL NOT NULL CHECK (amount >= 0),
  currency TEXT NOT NULL CHECK (currency IN ('UGX', 'USD')),
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'invoiced', 'paid')),
  due_date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_payment_milestones_project_id ON payment_milestones(project_id);
CREATE INDEX idx_payment_milestones_status ON payment_milestones(status);
CREATE INDEX idx_payment_milestones_due_date ON payment_milestones(due_date);
CREATE INDEX idx_payment_milestones_created_at ON payment_milestones(created_at);

-- ===== EXPENSES TABLE =====
CREATE TABLE expenses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  category TEXT NOT NULL,
  amount REAL NOT NULL CHECK (amount >= 0),
  currency TEXT NOT NULL CHECK (currency IN ('UGX', 'USD')),
  type TEXT NOT NULL CHECK (type IN ('recurring', 'one_off')),
  frequency TEXT CHECK (frequency IN ('monthly', 'quarterly', 'annual')),
  status TEXT NOT NULL DEFAULT 'due' CHECK (status IN ('due', 'paid', 'overdue')),
  project_id INTEGER REFERENCES projects(id) ON DELETE RESTRICT,
  due_date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_expenses_type ON expenses(type);
CREATE INDEX idx_expenses_status ON expenses(status);
CREATE INDEX idx_expenses_project_id ON expenses(project_id);
CREATE INDEX idx_expenses_due_date ON expenses(due_date);
CREATE INDEX idx_expenses_created_at ON expenses(created_at);

-- ===== ACCOUNTS TABLE =====
CREATE TABLE accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  type TEXT NOT NULL CHECK (type IN ('bank', 'mobile_money', 'cash')),
  currency TEXT NOT NULL CHECK (currency IN ('UGX', 'USD')),
  opening_balance REAL NOT NULL CHECK (opening_balance >= 0),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_accounts_type ON accounts(type);
CREATE INDEX idx_accounts_currency ON accounts(currency);
CREATE INDEX idx_accounts_created_at ON accounts(created_at);

-- ===== CASH TRANSACTIONS TABLE =====
CREATE TABLE cash_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE RESTRICT,
  type TEXT NOT NULL CHECK (type IN ('inflow', 'outflow')),
  amount REAL NOT NULL CHECK (amount > 0),
  currency TEXT NOT NULL CHECK (currency IN ('UGX', 'USD')),
  source_type TEXT NOT NULL,
  source_id INTEGER NOT NULL,
  transaction_date TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_cash_transactions_account_id ON cash_transactions(account_id);
CREATE INDEX idx_cash_transactions_type ON cash_transactions(type);
CREATE INDEX idx_cash_transactions_transaction_date ON cash_transactions(transaction_date);
CREATE INDEX idx_cash_transactions_source ON cash_transactions(source_type, source_id);
CREATE INDEX idx_cash_transactions_created_at ON cash_transactions(created_at);

-- ===== OPPORTUNITIES TABLE =====
CREATE TABLE opportunities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  client TEXT NOT NULL,
  description TEXT NOT NULL,
  estimated_value REAL NOT NULL CHECK (estimated_value >= 0),
  currency TEXT NOT NULL DEFAULT 'UGX' CHECK (currency IN ('USD', 'UGX')),
  probability REAL NOT NULL CHECK (probability >= 0 AND probability <= 100),
  stage TEXT NOT NULL DEFAULT 'lead' CHECK (stage IN ('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost')),
  source TEXT NOT NULL,
  owner INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  expected_close_date TEXT NOT NULL,
  project_type TEXT,
  auto_apply_template INTEGER DEFAULT 0,
  suggested_template_id INTEGER REFERENCES project_templates(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_opportunities_stage ON opportunities(stage);
CREATE INDEX idx_opportunities_owner ON opportunities(owner);
CREATE INDEX idx_opportunities_expected_close_date ON opportunities(expected_close_date);
CREATE INDEX idx_opportunities_created_at ON opportunities(created_at);
CREATE INDEX idx_opportunities_currency ON opportunities(currency);
CREATE INDEX idx_opportunities_project_type ON opportunities(project_type);
CREATE INDEX idx_opportunities_template ON opportunities(suggested_template_id);

-- ===== EXCHANGE RATES TABLE =====
CREATE TABLE exchange_rates (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  base_currency TEXT NOT NULL CHECK (base_currency = 'UGX'),
  quote_currency TEXT NOT NULL CHECK (quote_currency = 'USD'),
  rate REAL NOT NULL CHECK (rate > 0),
  effective_date TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX idx_exchange_rates_effective_date ON exchange_rates(effective_date);
CREATE INDEX idx_exchange_rates_created_at ON exchange_rates(created_at);
-- ===== ROLES TABLE =====
CREATE TABLE roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX idx_roles_name ON roles(name);
CREATE INDEX idx_roles_created_at ON roles(created_at);

-- ===== USER ROLES TABLE =====
CREATE TABLE user_roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX idx_user_roles_created_at ON user_roles(created_at);

-- ===== PERMISSIONS TABLE =====
CREATE TABLE permissions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
  resource TEXT NOT NULL,
  action TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_permissions_role_id ON permissions(role_id);
CREATE INDEX idx_permissions_resource ON permissions(resource);
CREATE INDEX idx_permissions_created_at ON permissions(created_at);

-- ===== ALERTS TABLE =====
CREATE TABLE alerts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL,
  severity TEXT NOT NULL CHECK (severity IN ('info', 'warning', 'critical')),
  entity_type TEXT NOT NULL,
  entity_id INTEGER,
  message TEXT NOT NULL,
  is_dismissed INTEGER NOT NULL DEFAULT 0,
  dismissed_at TEXT,
  dismissed_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_alerts_type ON alerts(type);
CREATE INDEX idx_alerts_severity ON alerts(severity);
CREATE INDEX idx_alerts_entity ON alerts(entity_type, entity_id);
CREATE INDEX idx_alerts_is_dismissed ON alerts(is_dismissed);
CREATE INDEX idx_alerts_created_at ON alerts(created_at);

-- ===== AUDIT LOGS TABLE =====
CREATE TABLE audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  entity_type TEXT NOT NULL,
  entity_id INTEGER NOT NULL,
  action TEXT NOT NULL CHECK (action IN ('create', 'update', 'delete', 'status_change', 'payment', 'file_upload')),
  before TEXT,
  after TEXT,
  ip_address TEXT,
  user_agent TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_action ON audit_logs(action);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);