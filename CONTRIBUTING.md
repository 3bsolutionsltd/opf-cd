CONTRIBUTING.md
OPF-CD Internal Operations System

This document defines mandatory contribution rules for the OPF-CD system.

These rules are non-negotiable.
Pull requests that violate them will be rejected without revision requests.

🧠 CORE PHILOSOPHY

OPF-CD is a contract-driven system.

Every component has one responsibility

Every service exposes facts, not decisions

Every API response is final and stable

“Helpful” code is considered incorrect code

If something feels clever, flexible, or reusable — it is wrong.

🚨 ABSOLUTE RULES (READ FIRST)
❌ You MUST NOT:

Add features not explicitly requested

Improve, refactor, or “optimize” existing logic

Anticipate future needs

Add abstractions “for later”

Add configuration flags

Add helper methods

Add convenience functions

Add caching, logging, retries, or guards

Rename fields in API responses

Combine multiple responsibilities in one file

✅ You MUST:

Follow existing contracts exactly

Match response shapes exactly

Keep code boring and obvious

Throw errors instead of hiding them

🧩 SERVICES — STRICT CONTRACT
Definition

A Service is a pure unit of business logic.

A service MUST:

Do exactly ONE thing

Expose only explicitly defined public methods

Return facts only

Be read-only unless explicitly stated

Throw on invalid data

A service MUST NOT:

Classify (no status, health, risk, labels)

Decide (no if-this-then-that conclusions)

Orchestrate multiple workflows

Iterate across unrelated entities

Call other services unless explicitly required

Add private/helper methods unless specified

If logic seems reusable, do not extract it.

🧠 SERVICE EXAMPLES

✅ Correct:

public function calculateProjectProgress(int $projectId): float


❌ Incorrect:

public function calculateAndClassifyProjectHealth(...)

🎯 CONTROLLERS — PASS-THROUGH ONLY

Controllers are not logic holders.

A controller MUST:

Call exactly one service per endpoint

Return service output unchanged

Perform no transformations

A controller MUST NOT:

Rename fields

Add metadata

Catch exceptions

Combine responses

Perform calculations

✅ Correct:

return response()->json(
    $service->method($id)
);


❌ Incorrect:

return response()->json([
  'data' => $service->method($id),
  'status' => 'success'
]);

📡 API CONTRACTS — IMMUTABLE

Once an API response shape exists:

It is final

It is versionless

It MUST NOT be altered

You MUST NOT:

Add fields

Remove fields

Rename fields

Change data types

Add derived values

Frontend logic must adapt — backend contracts do not.

🧮 FACTS VS DECISIONS
FACTS (allowed)

Percentages

Totals

Counts

Dates

Amounts

DECISIONS (restricted)

Health status

Risk levels

Warnings

Recommendations

Decisions belong only in designated synthesis services (e.g. ProjectHealthService).

🧪 ERROR HANDLING RULES

Invalid data → throw

Missing record → throw

Broken assumptions → throw

❌ Do NOT:

Swallow exceptions

Return partial data

Convert errors to booleans

Return empty arrays as “success”

Failures are signals, not UX concerns.

🔮 NO FUTURE-PROOFING POLICY

OPF-CD explicitly forbids:

Interfaces “for later”

Enums “just in case”

Hooks

Extension points

Configuration layers

You are solving today’s problem only.

🧭 WHEN IN DOUBT

If requirements are unclear:

STOP

Do NOT guess

Do NOT implement

Ask for clarification

Silence is preferred over wrong code.

🛑 PULL REQUEST REJECTION CRITERIA

Your PR will be rejected if any of the following are true:

Adds functionality not explicitly requested

Introduces helper methods without approval

Combines responsibilities

Alters an API response shape

Adds “quality of life” improvements

Refactors unrelated code

Makes code more abstract or flexible

✅ DEFINITION OF DONE

A contribution is correct only if:

It feels boring

It feels minimal

It feels obvious

Nothing extra was added

If it feels impressive — it is wrong.

🔒 FINAL NOTE

This system is architecture-first, not developer-first.

Discipline beats cleverness.
Stability beats elegance.
Contracts beat convenience.

By contributing, you agree to follow these rules without exception.