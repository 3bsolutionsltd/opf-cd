-- Rollback Migration: Remove lead qualification fields from opportunities table
-- Version: 1.0
-- Date: 2026-02-28

ALTER TABLE opportunities
DROP COLUMN IF EXISTS qualification_score,
DROP COLUMN IF EXISTS budget_confirmed,
DROP COLUMN IF EXISTS authority_level,
DROP COLUMN IF EXISTS need_validation,
DROP COLUMN IF EXISTS timeline_urgency,
DROP COLUMN IF EXISTS strategic_fit,
DROP COLUMN IF EXISTS disqualification_reason,
DROP COLUMN IF EXISTS last_contact_date;
