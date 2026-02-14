# Frontend Fix: Project-Specific Cash Flow

## Problem Identified
The Analytics Dashboard was showing **ALL cash transactions** across the entire system instead of filtering by the selected project.

**Example Issue:**
- Selected Project: CRM Website Redesign
- Expected Inflows: UGX 510,000
- Displayed Inflows: UGX 9,110,000 ❌ (includes all projects)
- Payment Gap: Incorrect calculation

## Root Cause
Frontend was calling `/finance/cash-flow` which returns **system-wide** cash flow, not project-specific data.

## Solution Implemented

### New Backend Endpoint
**Route:** `GET /api/projects/{projectId}/cash-flow`

**Response Format:**
```json
{
  "total_inflows": 510000.00,
  "total_outflows": 0.00,
  "net_cash_flow": 510000.00,
  "currency": "UGX"
}
```

### Implementation Details
- **Service:** `CashFlowService::getProjectCashFlow($projectId)`
- **Controller:** `ProjectController::getCashFlow($id)`
- **Route:** `/api/projects/{id}/cash-flow`
- **Permission Required:** `projects:view`

### Query Logic
```php
// Filters cash transactions by joining through payment_milestones
SELECT SUM(cash_transactions.amount)
FROM cash_transactions
INNER JOIN payment_milestones 
  ON cash_transactions.source_id = payment_milestones.id
  AND cash_transactions.source_type = 'payment_milestone'
WHERE payment_milestones.project_id = ?
  AND cash_transactions.type = 'inflow'
  AND cash_transactions.currency = ?
```

## Frontend Changes Required

### 1. Cash Flow Widget
**Current (WRONG):**
```javascript
// Fetches ALL cash flow
fetch('/api/finance/cash-flow')
```

**Fixed (CORRECT):**
```javascript
// Fetches project-specific cash flow
const projectId = selectedProject.id;
fetch(`/api/projects/${projectId}/cash-flow`)
```

### 2. Payment Gap Widget
The Payment Gap endpoint already filters by project correctly:
```javascript
// Already correct - no changes needed
fetch(`/api/projects/${projectId}/payment-gap`)
```

**Response includes:**
```json
{
  "gap_amount": 340000.00,
  "gap_percentage": 34.00,
  "earned_value": 850000.00,
  "received_value": 510000.00,
  "progress": 85.00,
  "contract_value": 1000000.00,
  "currency": "UGX"
}
```

### 3. Interpretation Logic
Update the display logic to correctly interpret the gap:

```javascript
// Positive gap = Client owes you (work ahead of payment)
// Negative gap = You owe work (payment ahead of work)

if (gap_amount > 0) {
  message = "Payment behind work";
  color = "green"; // Good for cash flow
} else if (gap_amount < 0) {
  message = "Payment ahead of work";
  color = "yellow"; // Client overpaid
} else {
  message = "Payment matches work";
  color = "blue";
}
```

## Verification - CRM Project Example

### Correct Values:
- **Contract Value:** UGX 1,000,000
- **Progress:** 85%
- **Earned Value:** UGX 850,000 (85% × 1M)
- **Received (Inflows):** UGX 510,000
- **Payment Gap:** +UGX 340,000
- **Interpretation:** Client owes UGX 340,000 (work done but not paid)

### Before Fix:
❌ Inflows: UGX 9,110,000 (ALL projects)
❌ Gap: Displayed incorrectly

### After Fix:
✅ Inflows: UGX 510,000 (CRM project only)
✅ Gap: +UGX 340,000 (Client owes)

## Testing Checklist
- [ ] Select "CRM Website Redesign" in project filter
- [ ] Verify Cash Flow shows: Inflows = 510,000 UGX
- [ ] Verify Payment Gap shows: 850,000 UGX (payment behind work)
- [ ] Switch to different project, verify numbers change
- [ ] Test with USD-currency project (e.g., E-commerce Platform)
- [ ] Verify dashboard shows "all projects" view when no filter selected

## API Endpoints Summary

| Endpoint | Scope | Use Case |
|----------|-------|----------|
| `/finance/cash-flow` | System-wide | Total cash flow across all projects |
| `/projects/{id}/cash-flow` | Project-specific | Cash flow for single project |
| `/projects/{id}/payment-gap` | Project-specific | Payment gap analysis |
| `/projects/{id}/progress` | Project-specific | Task completion progress |
| `/projects/{id}/health` | Project-specific | Project health status |

## Notes
- Outflows (expenses) are not currently linked to projects, so `total_outflows` will be 0
- Future enhancement: Add project-expense relationship to track project-specific costs
- Currency filtering ensures only matching currency transactions are included
