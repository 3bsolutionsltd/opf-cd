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
Weighted Pipeline Value =
Σ(opportunity.value × probability / 100)

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
