-- Migration: Create cash_transactions table
-- Description: Cash transactions (inflows/outflows) for accounts in the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create transaction type enum
CREATE TYPE transaction_type AS ENUM (
  'inflow',
  'outflow'
);

-- Create cash_transactions table
CREATE TABLE cash_transactions (
  id SERIAL PRIMARY KEY,
  account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE RESTRICT,
  type transaction_type NOT NULL,
  amount NUMERIC(15,2) NOT NULL CHECK (amount > 0),
  currency currency_code NOT NULL,
  source_type VARCHAR(50) NOT NULL,
  source_id INTEGER NOT NULL,
  transaction_date DATE NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE INDEX idx_cash_transactions_account_id ON cash_transactions(account_id);
CREATE INDEX idx_cash_transactions_type ON cash_transactions(type);
CREATE INDEX idx_cash_transactions_transaction_date ON cash_transactions(transaction_date);
CREATE INDEX idx_cash_transactions_source ON cash_transactions(source_type, source_id);
CREATE INDEX idx_cash_transactions_created_at ON cash_transactions(created_at);

-- Add comments for documentation
COMMENT ON TABLE cash_transactions IS 'Cash transactions are append-only audit records - never updated or deleted';
COMMENT ON COLUMN cash_transactions.account_id IS 'Account this transaction belongs to - transactions cannot be cascade deleted';
COMMENT ON COLUMN cash_transactions.type IS 'Transaction type: inflow (money in) or outflow (money out)';
COMMENT ON COLUMN cash_transactions.amount IS 'Transaction amount - must be positive';
COMMENT ON COLUMN cash_transactions.currency IS 'Currency code for the transaction';
COMMENT ON COLUMN cash_transactions.source_type IS 'Origin type (e.g., project_payment, expense)';
COMMENT ON COLUMN cash_transactions.source_id IS 'ID of the source record (payment_milestones.id or expenses.id)';
COMMENT ON COLUMN cash_transactions.transaction_date IS 'Date the transaction occurred';
