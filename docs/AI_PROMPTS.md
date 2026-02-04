# AI PROMPTS — OPF-CD

This file defines the **approved prompts** for using AI tools
(Claude, Copilot, ChatGPT, Cursor) in this repository.

⚠️ ALWAYS begin every AI session with:

Follow the rules in docs/copilot_rules.md

---

## PROMPT 1 — BLADE DASHBOARD SECTION

Use this prompt when creating or editing a **Blade dashboard section**.

Purpose:
- Enforce read-only UI
- Prevent frontend logic
- Prevent metric derivation
- Enforce API-as-source-of-truth

Prompt:

Follow the rules in docs/copilot_rules.md.

Create a Blade view that:
- Fetches data from the provided API endpoint
- Displays fields EXACTLY as returned
- Performs NO calculations
- Performs NO transformations
- Performs NO conditional business logic
- Uses Blade + Alpine.js only
- Uses Alpine.js ONLY for data fetching and rendering

API endpoint:
<PASTE ENDPOINT HERE>

Expected fields:
<PASTE RESPONSE CONTRACT HERE>

Return ONLY the Blade file contents.
Do NOT explain.
Do NOT optimize.
Do NOT add features.

---

## PROMPT 2 — CONTROLLER VERIFICATION

Use this to validate a controller.

Purpose:
- Keep controllers thin
- Prevent logic leakage

Prompt:

Follow the rules in docs/copilot_rules.md.

Review the following controller.

Rules:
- Controller must only call ONE service
- Controller must NOT transform data
- Controller must NOT calculate anything
- Controller must return service output directly

Respond with ONLY one of:

VALID

INVALID: <specific rule violated>

---

## PROMPT 3 — API CONTRACT VALIDATION

Use this to verify that an API response matches its contract.

Purpose:
- Prevent silent API drift

Prompt:

Follow the rules in docs/copilot_rules.md.

Compare the EXPECTED API CONTRACT with the ACTUAL RESPONSE.

Rules:
- No extra fields
- No missing fields
- No renamed fields
- No derived values

Respond with ONLY one of:

VALID

INVALID: <reason>

---

## PROMPT 4 — OVERENGINEERING CHECK

Use this when code feels “smart”, complex, or suspicious.

Purpose:
- Detect rule violations early

Prompt:

Follow the rules in docs/copilot_rules.md.

Review the following code ONLY for:

- Overengineering
- Multiple responsibilities
- Hidden orchestration
- Helper method creep
- Decision logic in fact services
- Frontend logic leakage

Respond with ONLY:

CLEAN

VIOLATION: <rule broken>

---

## PROMPT 5 — NEXT ACTION GATE

Use this instead of asking:
“What should I do next?”

Purpose:
- Prevent roadmap drift

Prompt:

Follow the rules in docs/copilot_rules.md.

Based on the CURRENT STATE of the OPF-CD system,
identify the SINGLE next allowed action.

Do NOT list options.
Do NOT explain.
Return ONE sentence only.

---

## FINAL NOTE

- docs/copilot_rules.md = LAW
- docs/AI_PROMPTS.md = HOW TO APPLY THE LAW

If a prompt conflicts with the rules file,
the rules file always wins.
