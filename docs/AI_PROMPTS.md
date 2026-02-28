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

Based on the CURRENT STATE and PRODUCTION_ROADMAP.md of the OPF-CD system
identify the next allowed actions.

Do NOT list options.
Do NOT explain.
Return ONE sentence only.

---
## PROMPT — DASHBOARD ROUTING (BLADE)

Follow the rules in docs/copilot_rules.md.

Context:
- OPF-CD Phase 1 APIs are COMPLETE and LOCKED.
- Blade views already exist under:
  resources/views/dashboard/
- API routes live in routes/api.php and MUST NOT be modified.

Task:
Create the MINIMUM web-layer wiring required to render existing Blade dashboard views.

Requirements:
- Create a DashboardController that ONLY maps routes to Blade views.
- Do NOT inject services.
- Do NOT call APIs inside the controller.
- Do NOT add logic, calculations, or transformations.
- Each method returns exactly one Blade view with minimal parameters (e.g. projectId).

Routes:
- Register routes ONLY in routes/web.php
- Use prefix: /dashboard

Views to wire:
- /dashboard/project-progress/{id} → dashboard.project-progress
- /dashboard/payment-gap/{id} → dashboard.payment-gap
- /dashboard/project-health/{id} → dashboard.project-health
- /dashboard/cash-flow → dashboard.cash-flow
- /dashboard/upcoming-expenses → dashboard.upcoming-expenses
- /dashboard/sales-pipeline → dashboard.sales-pipeline

Output:
- DashboardController.php
- routes/web.php additions only

Do NOT explain.
Do NOT suggest improvements.
Do NOT modify existing files beyond what is explicitly requested.

---

## PROMPT — TASK BREAKDOWN (`*._TASK_BREAKDOWN.md`)

Use this prompt to generate a `_TASK_BREAKDOWN.md` file for a planned feature.

Naming convention: `FEATURE_NAME._TASK_BREAKDOWN.md`

Purpose:
- Produce a structured, minimal implementation plan for a new feature
- Break the feature into ordered phases and atomic tasks
- Identify services, controllers, migrations, and routes required
- Serve as the authoritative checklist before any code is written

Prompt:

Follow the rules in docs/copilot_rules.md.

You are producing a `_TASK_BREAKDOWN.md` for the following feature:

Feature Name: <FEATURE NAME>

Relevant spec section from docs/_truth.md:
<PASTE RELEVANT SECTION>

Rules:
- Break the feature into sequentially numbered phases (e.g. Phase X.1, X.2 …)
- Each phase contains atomic, testable tasks
- Each task names exactly ONE artefact: migration, service, controller, route, or view
- No task may combine multiple artefacts
- No task may include future-proofing, abstractions, or optimisations
- No task may add functionality not stated in the spec
- List dependent tasks in order (migrations before services, services before controllers)

Output format (repeat for each phase):

### Phase X.N — <Phase Title>

- [ ] Task 1: <one artefact, one action>
- [ ] Task 2: <one artefact, one action>

Do NOT explain.
Do NOT suggest improvements.
Do NOT add tasks beyond what is explicitly required by the spec.

---

## FINAL NOTE

- docs/copilot_rules.md = LAW
- docs/AI_PROMPTS.md = HOW TO APPLY THE LAW

If a prompt conflicts with the rules file,
the rules file always wins.
