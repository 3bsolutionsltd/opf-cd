# OPF-CD — Source of Truth

This document is the authoritative reference for the internal Operations, Projects & Finance Command Dashboard.
If a rule or structure is not defined here, it must not be invented in code.

---

## Core Principles
- Work progress ≠ payment received
- Financial records are immutable after posting
- All calculations are deterministic and explainable
- Controllers contain no business logic
- Services contain all calculations
- Dashboards are derived, never stored

---

## Modules
1. Projects & Tasks
2. Payments & Billing
3. Company Expenses
4. Cash Management
5. Opportunities Pipeline
6. Project Health Index (PHI)
7. Alerts & Reports

---

## Project Progress
Project progress is calculated as the weighted sum of task progress.

Formula:
Project Progress = Σ(task.progress × task.weight / 100)

Rules:
- Task weights must sum to 100
- Task progress is between 0 and 100
- Completed tasks must have progress = 100

---

## Payments
- Projects have payment milestones
- Payments can be Pending, Invoiced, or Paid
- Payment received percentage is:
  (Total Paid / Contract Value) × 100

---

## Payment Gap
Payment Gap = Project Progress % − Payment Received %

If Payment Gap > 20%, the project is financially at risk.

---

## Expenses
- Expenses are either recurring or one-off
- Recurring expenses auto-generate future instances
- Paid expenses cannot be edited

---

## Cash
Cash at Hand = Opening Balance + Inflows − Outflows

Cash Runway (months) =
Cash at Hand / Average Monthly Burn

---

## Opportunities
Each opportunity captures estimated value in its respective currency (UGX or USD) to maintain fluidity without conversion requirements.

Weighted Pipeline Value =
Σ(opportunity.value × probability / 100)

Note: Pipeline calculations are performed per currency. Multi-currency pipeline totals require explicit conversion at display time.

### Won Opportunity to Project Conversion

When an opportunity's stage changes to "won", the system can create a project linked to that opportunity. There are two creation methods: automatic and manual.

#### Automatic Creation

When an opportunity's stage changes to "won" for the **first time**, the system automatically creates a project.

**Duplicate Prevention**: If any projects already exist for the opportunity (even if it was previously won, then changed, then won again), automatic creation is skipped. This prevents duplicate projects from stage transitions.

**Automatic Project Fields**:
- `name`: Auto-generated as "{client} - Project ({timestamp})"
- `client`: Copied from opportunity.client
- `contract_value`: Set to opportunity.estimated_value initially
- `contract_currency`: Set to opportunity.currency
- `start_date`: Set to current date when won
- `end_date`: NULL (requires manual entry)
- `status`: Set to "planned"
- `project_lead_id`: NULL (requires manual assignment)
- `opportunity_id`: Foreign key to originating opportunity

#### Manual Creation

For multi-phase opportunities, users can manually create additional projects via:
- **Endpoint**: `POST /api/opportunities/{opportunityId}/projects`
- **Permission**: Requires `opportunities.edit` permission
- **Stage-Agnostic**: Works regardless of opportunity stage (not only "won")

**Manual Project Fields**:
- `name`: User-provided (required)
- `client`: Always copied from opportunity (not user-editable)
- `contract_value`: User-provided (required)
- `contract_currency`: User-provided (required, must be USD or UGX)
- `start_date`: User-provided (required, Y-m-d format)
- `end_date`: User-provided (optional, nullable, must be >= start_date)
- `status`: User-provided (optional, defaults to "planned")
- `project_lead_id`: User-provided (optional, nullable, must exist in users table)
- `opportunity_id`: Foreign key to originating opportunity

#### Multi-Phase Support

Multiple projects can be linked to a single opportunity. Common scenarios:
- **Phase 1**: Initial implementation project (auto-created when won)
- **Phase 2**: Enhancement project (manually created later)
- **Phase 3**: Maintenance project (manually created after delivery)

#### Project Independence

Once created, projects have independent lifecycles:
- Changing opportunity stage (e.g., won → negotiation) does NOT affect existing projects
- Projects are never auto-cancelled or auto-deleted
- Projects remain linked via `opportunity_id` for historical reference
- All state changes are logged in audit trail

**Rationale**: In multi-phase scenarios, it's impossible to determine which project(s) should be affected by opportunity stage changes. Manual project management is required.

#### Listing Projects for Opportunity

- **Endpoint**: `GET /api/opportunities/{opportunityId}/projects`
- **Permission**: Requires `opportunities.view` permission
- **Returns**: All projects linked to the opportunity, ordered by creation date (newest first)

---

## Project Health Index (PHI)

PHI Score = 
(time_score × 0.3) +
(payment_score × 0.3) +
(blocker_score × 0.2) +
(overdue_score × 0.2)

### Factor Calculations

**Time Score (30%):**
- Expected Progress = (Days Elapsed / Total Project Days) × 100
- Time Score = (Actual Progress / Expected Progress) × 100
- Clamped to 0-100
- If project past end date and incomplete, score penalized

**Payment Score (30%):**
- If Payment Gap > 0 (owed money): Score = 100 - |Payment Gap %|
- If Payment Gap < 0 (ahead on payment): Score = 100
- Payment Gap from Payment Gap formula above

**Blocker Score (20%):**
- Blocker Score = 100 - (Blocked Tasks Count × 10)
- Penalty: 10 points per blocked task
- Minimum score: 0

**Overdue Score (20%):**
- Overdue Score = 100 - (Overdue Milestones Count × 15)
- Penalty: 15 points per overdue milestone (past due_date and status != 'paid')
- Minimum score: 0

Health Bands:
- Green ≥ 80
- Yellow 50–79
- Red < 50
