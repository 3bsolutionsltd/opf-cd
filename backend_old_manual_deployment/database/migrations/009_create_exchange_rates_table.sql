-- Migration: Create exchange_rates table
-- Description: Exchange rates for UGX/USD currency pair in the OPF-CD system
-- Version: 1.0
-- Date: 2026-02-02

-- Create exchange_rates table
CREATE TABLE exchange_rates (
  id SERIAL PRIMARY KEY,
  base_currency currency_code NOT NULL CHECK (base_currency = 'UGX'),
  quote_currency currency_code NOT NULL CHECK (quote_currency = 'USD'),
  rate NUMERIC(10,6) NOT NULL CHECK (rate > 0),
  effective_date DATE NOT NULL UNIQUE,
  created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes
CREATE UNIQUE INDEX idx_exchange_rates_effective_date ON exchange_rates(effective_date);
CREATE INDEX idx_exchange_rates_created_at ON exchange_rates(created_at);

-- Add comments for documentation
COMMENT ON TABLE exchange_rates IS 'Exchange rates for UGX/USD - append-only historical records';
COMMENT ON COLUMN exchange_rates.base_currency IS 'Base currency - must be UGX';
COMMENT ON COLUMN exchange_rates.quote_currency IS 'Quote currency - must be USD';
COMMENT ON COLUMN exchange_rates.rate IS 'Exchange rate (UGX per USD) - must be positive';
COMMENT ON COLUMN exchange_rates.effective_date IS 'Date this rate becomes effective - must be unique';
