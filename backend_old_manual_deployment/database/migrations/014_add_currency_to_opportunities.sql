-- Migration: Add currency column to opportunities table
-- Description: Add currency field to track opportunity values in different currencies
-- Version: 1.1
-- Date: 2026-02-20

-- Add currency column with default value
ALTER TABLE opportunities 
ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'UGX' 
CHECK (currency IN ('USD', 'UGX'));

-- Create index for currency queries
CREATE INDEX idx_opportunities_currency ON opportunities(currency);

-- Add comment for documentation
COMMENT ON COLUMN opportunities.currency IS 'Currency for estimated value (USD or UGX)';
