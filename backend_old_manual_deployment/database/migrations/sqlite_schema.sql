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
  probability REAL NOT NULL CHECK (probability >= 0 AND probability <= 100),
  stage TEXT NOT NULL DEFAULT 'lead' CHECK (stage IN ('lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost')),
  source TEXT NOT NULL,
  owner INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
  expected_close_date TEXT NOT NULL,
  qualification_score INTEGER NOT NULL DEFAULT 0 CHECK (qualification_score >= 0 AND qualification_score <= 100),
  budget_confirmed TEXT NOT NULL DEFAULT 'unknown' CHECK (budget_confirmed IN ('yes', 'no', 'unknown')),
  authority_level TEXT NOT NULL DEFAULT 'unknown' CHECK (authority_level IN ('decision_maker', 'influencer', 'user', 'unknown')),
  need_validation TEXT NOT NULL DEFAULT 'unknown' CHECK (need_validation IN ('critical', 'important', 'nice_to_have', 'unknown')),
  timeline_urgency TEXT NOT NULL DEFAULT 'unclear' CHECK (timeline_urgency IN ('immediate', 'this_quarter', 'next_quarter', 'unclear')),
  strategic_fit TEXT NOT NULL DEFAULT 'cold_lead' CHECK (strategic_fit IN ('existing_client', 'referral', 'target_industry', 'cold_lead')),
  disqualification_reason TEXT NULL,
  last_contact_date TEXT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX idx_opportunities_stage ON opportunities(stage);
CREATE INDEX idx_opportunities_owner ON opportunities(owner);
CREATE INDEX idx_opportunities_expected_close_date ON opportunities(expected_close_date);
CREATE INDEX idx_opportunities_created_at ON opportunities(created_at);

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
