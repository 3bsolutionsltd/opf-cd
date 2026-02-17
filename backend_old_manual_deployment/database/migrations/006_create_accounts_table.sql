-- Migration: Create accounts table
-- Description: Financial accounts (bank, mobile money, cash) for the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create account type enum
CREATE TYPE account_type AS ENUM (
  'bank',
  'mobile_money',
  'cash'
);

-- Create accounts table
CREATE TABLE accounts (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type account_type NOT NULL,
  currency currency_code NOT NULL,
  opening_balance NUMERIC(15,2) NOT NULL CHECK (opening_balance >= 0),
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_accounts_type ON accounts(type);
CREATE INDEX idx_accounts_currency ON accounts(currency);
CREATE INDEX idx_accounts_created_at ON accounts(created_at);

-- Add comments for documentation
COMMENT ON TABLE accounts IS 'Financial accounts - current balance is calculated, not stored (opening_balance + inflows - outflows)';
COMMENT ON COLUMN accounts.name IS 'Account name or description (e.g., ABC Bank Checking, MTN Mobile Money)';
COMMENT ON COLUMN accounts.type IS 'Account type: bank, mobile_money, or cash';
COMMENT ON COLUMN accounts.currency IS 'Currency code for the account';
COMMENT ON COLUMN accounts.opening_balance IS 'Initial account balance - must be non-negative';
