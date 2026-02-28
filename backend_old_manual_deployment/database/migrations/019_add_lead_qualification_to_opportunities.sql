-- Migration: Add lead qualification fields to opportunities table
-- Description: BANT (Budget, Authority, Need, Timeline) qualification fields for lead scoring
-- Version: 1.0
-- Date: 2026-02-28

ALTER TABLE opportunities
ADD COLUMN qualification_score INTEGER NOT NULL DEFAULT 0 CHECK (qualification_score >= 0 AND qualification_score <= 100),
ADD COLUMN budget_confirmed VARCHAR(20) NOT NULL DEFAULT 'unknown' CHECK (budget_confirmed IN ('yes', 'no', 'unknown')),
ADD COLUMN authority_level VARCHAR(30) NOT NULL DEFAULT 'unknown' CHECK (authority_level IN ('decision_maker', 'influencer', 'user', 'unknown')),
ADD COLUMN need_validation VARCHAR(30) NOT NULL DEFAULT 'unknown' CHECK (need_validation IN ('critical', 'important', 'nice_to_have', 'unknown')),
ADD COLUMN timeline_urgency VARCHAR(30) NOT NULL DEFAULT 'unclear' CHECK (timeline_urgency IN ('immediate', 'this_quarter', 'next_quarter', 'unclear')),
ADD COLUMN strategic_fit VARCHAR(30) NOT NULL DEFAULT 'cold_lead' CHECK (strategic_fit IN ('existing_client', 'referral', 'target_industry', 'cold_lead')),
ADD COLUMN disqualification_reason TEXT NULL,
ADD COLUMN last_contact_date DATE NULL;

COMMENT ON COLUMN opportunities.qualification_score IS 'Calculated BANT score 0-100; 70+ = HOT, 40-69 = WARM, <40 = COLD';
COMMENT ON COLUMN opportunities.budget_confirmed IS 'Has prospect confirmed budget availability?';
COMMENT ON COLUMN opportunities.authority_level IS 'Level of decision-making authority of current contact';
COMMENT ON COLUMN opportunities.need_validation IS 'How critical is the need being addressed?';
COMMENT ON COLUMN opportunities.timeline_urgency IS 'How urgent is the purchase timeline?';
COMMENT ON COLUMN opportunities.strategic_fit IS 'How well does this opportunity align with our strategy?';
COMMENT ON COLUMN opportunities.disqualification_reason IS 'Reason for disqualification, if applicable';
COMMENT ON COLUMN opportunities.last_contact_date IS 'Date of most recent contact with prospect';
