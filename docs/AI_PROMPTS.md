Use this to verify that an API response matches its contract.

Purpose:
- Prevent silent API drift

Prompt:

Follow the rules in docs/copilot_rules.md.

Compare the EXPECTED API CONTRACT with the ACTUAL RESPONSE.

Rules:

No extra fields

No missing fields

No renamed fields

No derived values

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

Overengineering

Multiple responsibilities

Hidden orchestration

Helper method creep

Decision logic in fact services

Respond with ONLY:

CLEAN

VIOLATION: <rule broken>


---

## PROMPT 5 — NEXT ACTION GATE

Use this instead of asking open-ended questions like
“what should I do next?”

Purpose:
- Prevent roadmap drift

Prompt:

Follow the rules in docs/copilot_rules.md.

Based on the current state of the OPF-CD system,
identify the SINGLE next allowed action.

Do NOT list options.
Do NOT explain.


---

## FINAL NOTE

This file is a **playbook**, not a rulebook.

- `docs/copilot_rules.md` = LAW (short, strict, authoritative)
- `docs/AI_PROMPTS.md` = HOW TO APPLY THE LAW

If a prompt conflicts with `copilot_rules.md`,
the rules file always wins.