# AI PROMPTS — DATA MODEL AUDIT (OPF-CD)

These prompts are used to **audit existing database schemas**
(PostgreSQL tables, SQL migrations, or ERDs) against OPF-CD system truths.

⚠️ ALWAYS start every AI session with:

Follow the rules in docs/copilot_rules.md
---
## PROMPT 1 — TABLE RESPONSIBILITY AUDIT
Use this to verify that a table has **one clear purpose**.

Prompt:

Review the following database table definition.

Rules:
- A table must represent exactly ONE business concept
- Columns must directly support that concept
- No mixed responsibilities
- No derived or computed fields
- No workflow, health, or status inference unless explicitly defined in docs/_truth.md

Respond with ONLY one of:

VALID

INVALID: <single sentence explaining the violation>

---

## PROMPT 2 — COLUMN LEGITIMACY AUDIT

Use this to verify that **every column has a reason to exist**.

Prompt:
We're going to run audit checks against the data the database schemas created in Phase 2(2.1-2.7) yesterday. Follow the following prompts

Audit the following table columns.

Rules:
- Every column must map to a business fact
- No UI-only columns
- No cached or derived values
- No future-proofing
- No “might be useful later” fields

For EACH column respond with:

OK — <brief justification>

or

INVALID — <why this column should not exist>

---

## PROMPT 3 — STATUS / ENUM AUDIT

Use this to audit status fields and enums.

Prompt:

Review the following status or enum definition.

Rules:
- Status must represent a real-world state
- No computed, inferred, or judgment-based states
- No overlap between enum values
- Enum must match wording in docs/_truth.md exactly

Respond with ONLY:

VALID

or

INVALID: <specific enum value that violates the rules>

---

## PROMPT 4 — RELATIONSHIP AUDIT

Use this to validate foreign keys and relationships.

Prompt:

Audit the relationships in the following schema.

Rules:
- Relationships must reflect real ownership or dependency
- No convenience relationships
- No circular dependencies unless unavoidable
- Deletion rules must preserve business truth (RESTRICT vs CASCADE must be justified)

Respond with:

VALID

or

INVALID: <specific relationship and why it violates truth>

---

## PROMPT 5 — TEMPORAL DATA AUDIT

Use this to verify date/time fields.

Prompt:

Review the date and time fields in this table.

Rules:
- Dates must represent real-world events
- No redundant timestamps
- created_at / updated_at only if they serve audit needs
- No artificial timelines

Respond with ONLY:

VALID

or

INVALID: <which date field violates the rules>

---

## PROMPT 6 — DERIVED DATA DETECTION

Use this to catch hidden calculations in schemas.

Prompt:

Inspect this table for derived or computed data.

Rules:
- No totals, percentages, scores, health, or progress fields
- Calculations must live in services only
- Database stores facts, never conclusions

Respond with ONLY:

CLEAN

or

DERIVED DATA FOUND: <field name>

---

## PROMPT 7 — TRUTH ALIGNMENT CHECK

Use this as the final gate.

Prompt:

Compare this table definition against docs/_truth.md.

Rules:
- Every column must be traceable to an explicit truth
- No missing required truths
- No invented concepts

Respond with ONLY:

ALIGNED

or

MISALIGNED: <missing or extra concept>

---

## FINAL RULE

If ANY prompt returns INVALID, MISALIGNED, or DERIVED DATA FOUND:

STOP.
Do NOT patch.
Do NOT extend.
Redesign the table.

This file is a diagnostic tool, not a design guide.