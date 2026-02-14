# Frontend API Endpoints Reference

## Analytics Dashboard - Endpoint Selection

### Default Dashboard (All Projects)
When no project is selected or "All Projects" is displayed, use these endpoints:

```
GET /api/dashboard/aggregate/progress
GET /api/dashboard/aggregate/payment-gap
GET /api/dashboard/aggregate/health
GET /api/finance/cash-flow
```

### Project-Specific Dashboard
When a specific project is selected from the dropdown, use these endpoints with the project ID:

```
GET /api/projects/{id}/progress
GET /api/projects/{id}/payment-gap
GET /api/projects/{id}/health
GET /api/projects/{id}/cash-flow
```

## Example: CRM Website Redesign (project_id = 5)

### ✅ Correct Endpoints
```
GET /api/projects/5/progress
GET /api/projects/5/payment-gap
GET /api/projects/5/health
GET /api/projects/5/cash-flow
```

### ❌ Incorrect (Don't use these for filtered view)
```
GET /api/finance/cash-flow  # This returns ALL projects' transactions
```

## Response Examples

### Project Progress
```json
{
  "progress": 85.0,
  "total_weight": 100.0
}
```

### Project Payment Gap
```json
{
  "gap_amount": 340000.0,
  "gap_percentage": 34.0,
  "progress": 85.0,
  "earned_value": 850000.0,
  "received_value": 510000.0,
  "contract_value": 1000000.0
}
```

**CRITICAL: Gap Sign Interpretation**

The `gap_amount` is calculated as: **Earned Value - Received Value**

```javascript
// Frontend display logic
if (gap_amount > 0) {
    // Client owes you money
    label = "Payment behind work"
    // or "Client owes you"
    color = "green" // positive for you
} else if (gap_amount < 0) {
    // You owe work to client
    label = "Payment ahead of work"
    // or "Work behind payment"
    color = "red" // you need to deliver
} else {
    label = "Perfect balance"
    color = "neutral"
}
```

**Examples:**
- `gap_amount = 340000` → "Client owes you 340,000" (✅ Good for you)
- `gap_amount = -340000` → "Payment ahead of work by 340,000" (⚠️ You owe work)
- `gap_amount = 0` → "Perfect balance" (✅ Even)

### Project Cash Flow
```json
{
  "total_inflows": 510000.0,
  "total_outflows": 0.0,
  "net_cash_flow": 510000.0,
  "currency": "UGX"
}
```

### Project Health
```json
{
  "project_id": 5,
  "health_status": "amber",
  "status_label": "At Risk",
  "status_description": "Project has some concerns that need attention.",
  "score": 75,
  "signals": {
    "payment_gap_percentage": 34.0,
    "payment_gap_amount": 340000.0,
    "earned_value": 850000.0,
    "received_value": 510000.0,
    "project_progress": 85.0,
    "project_status": "active",
    "weighted_pipeline_value": 210000.0,
    "expenses_next_30_days": 1
  },
  "reasons": [
    "Payment gap exceeds 20% of earned value"
  ],
  "details": [
    "Payment behind schedule: Client owes 34% of earned value (340,000 out of 850,000 earned).",
    "Project 85% complete and on track.",
    "Pipeline value: 210,000 (healthy)",
    "1 expense(s) due in next 30 days."
  ],
  "recommendations": [
    "Follow up with client on outstanding payment"
  ]
}
```

**Frontend Display Strategy:**

```jsx
// Main health card
<HealthCard>
  <StatusBadge color={getColor(health.health_status)}>
    {health.status_label} ({health.score}/100)
  </StatusBadge>
  <Description>{health.status_description}</Description>
  
  {/* Show key signals */}
  <Signals>
    <Signal>
      <Label>Progress:</Label> {health.signals.project_progress}%
    </Signal>
    <Signal>
      <Label>Payment Gap:</Label> 
      {formatCurrency(health.signals.payment_gap_amount)} 
      ({health.signals.payment_gap_percentage}%)
    </Signal>
  </Signals>
  
  {/* Show detailed explanations */}
  {health.details.length > 0 && (
    <Details>
      <h4>Health Analysis:</h4>
      <ul>
        {health.details.map(detail => <li>{detail}</li>)}
      </ul>
    </Details>
  )}
  
  {/* Show actionable recommendations */}
  {health.recommendations.length > 0 && (
    <Recommendations>
      <h4>Recommended Actions:</h4>
      <ul>
        {health.recommendations.map(rec => (
          <li>
            <Icon>💡</Icon> {rec}
          </li>
        ))}
      </ul>
    </Recommendations>
  )}
  
  {/* Optionally show reasons for penalties */}
  {health.reasons.length > 0 && (
    <Reasons>
      <small>Issues detected:</small>
      {health.reasons.map(reason => <Badge>{reason}</Badge>)}
    </Reasons>
  )}
</HealthCard>
```

**Health Status Mapping:**
- `health_status: "green"` → `status_label: "Healthy"` → Score 80-100 → Color: Green
- `health_status: "amber"` → `status_label: "At Risk"` → Score 50-79 → Color: Yellow/Orange
- `health_status: "red"` → `status_label: "Critical"` → Score 0-49 → Color: Red

**Scoring Model (Penalty-Based):**
Starts at 100, penalties applied:
- Payment gap > 40%: -40 points
- Payment gap > 20%: -25 points
- Progress < 50% (active project): -15 points
- No pipeline value: -20 points
- More than 5 expenses in next 30 days: -15 points

**CRM Project Example:**
- Score: 75 (starts at 100)
- Penalty: -25 (gap 34% > 20%)
- Result: "amber" / "at-risk"
- Reason: Payment gap (client owes 340K, 34% of contract)

## Current Issue Fixed

**Problem:** Frontend was calling `/api/finance/cash-flow` even when a specific project was selected, showing totals across ALL projects instead of the filtered project.

**Solution:** Switch between aggregate and project-specific endpoints based on the project dropdown selection.

**Backend Fix Applied:** Fixed `PaymentGapService` to use correct `source_type` value (`payment_milestone` instead of `project_payment`) for accurate received payment calculations.

## Implementation Checklist for Frontend

- [ ] Detect when project filter changes
- [ ] Use aggregate endpoints when no project selected
- [ ] Use project-specific endpoints when project selected
- [ ] Pass project ID to all four analytics calls
- [ ] Update UI labels (e.g., "Payment ahead of work" vs "Client owes you")
- [ ] Handle currency properly (UGX vs USD)
- [ ] Test with different projects to verify filtering works

## Test Cases

### Test 1: Default Dashboard
- Select "All Projects" 
- Should show: 9,110,000 UGX inflows (all transactions)

### Test 2: CRM Project (Complete Analytics)
- Select "CRM Website Redesign"

**Expected Results:**
```
Progress: 85%
  - Task weight contributions: 85/100

Payment Gap: +340,000 UGX (34%)
  - Label: "Client owes you 340,000" ✅
  - OR: "Payment behind work" ✅
  - NOT: "Payment ahead of work" ❌
  - Earned: 850,000 UGX
  - Received: 510,000 UGX

Cash Flow:
  - Inflows: 510,000 UGX
  - Outflows: 0 UGX
  - Net: 510,000 UGX

Health: Amber / At Risk (Score: 75)
  - Icon: ⚠️ Warning
  - Color: Yellow/Orange
  - Description: "Project has some concerns that need attention"
  - Score: 75/100
  
  Analysis:
  ✓ "Payment behind schedule: Client owes 34% (340K of 850K earned)"
  ✓ "Project 85% complete and on track"
  ✓ "Pipeline value: 210,000 (healthy)"
  ✓ "1 expense(s) due in next 30 days"
  
  Recommendations:
  💡 "Follow up with client on outstanding payment"
```

### Test 3: Switching Projects
- Select Project A → verify numbers
- Select Project B → verify numbers change
- Select "All Projects" → verify aggregate totals

## Common Frontend Bugs

### Bug 1: Wrong Gap Interpretation ❌
**Symptom:** Showing "Payment ahead of work" when gap is positive

**Example:**
```
Gap: +340,000
Label: "Payment ahead of work" ❌ WRONG
```

**Fix:** Flip the logic
```javascript
// CORRECT
if (gap_amount > 0) {
    return "Client owes you"; // or "Payment behind work"
} else if (gap_amount < 0) {
    return "Payment ahead of work";
}
```

**Quick Reference Table:**

| Gap Value | Earned Value | Received Value | Correct Label | Meaning |
|-----------|-------------|----------------|---------------|---------|
| +340,000 | 850,000 | 510,000 | "Client owes you 340K" | ✅ Good - client needs to pay |
| +340,000 | 850,000 | 510,000 | "Payment behind work" | ✅ Good - same meaning |
| -100,000 | 400,000 | 500,000 | "Payment ahead of work by 100K" | ⚠️ You owe work |
| -100,000 | 400,000 | 500,000 | "Work behind payment" | ⚠️ Same meaning |
| 0 | 500,000 | 500,000 | "Perfect balance" | ✅ Even |

### Bug 2: Using Wrong Endpoint for Filtered View ❌
**Symptom:** Cash flow shows 9,110,000 when CRM project selected (should show 510,000)

**Fix:** Use project-specific endpoints when project is selected
```javascript
// WRONG
const url = `/api/finance/cash-flow`; // Always returns all projects

// CORRECT
const url = projectId 
    ? `/api/projects/${projectId}/cash-flow`  // Filtered
    : `/api/finance/cash-flow`;               // All projects
```

### Bug 3: Not Clearing Previous Project Data ❌
**Symptom:** Old project data briefly visible when switching projects

**Fix:** Reset/clear state before fetching new project data
```javascript
// Clear previous data
setCashFlow(null);
setPaymentGap(null);

// Then fetch new data
fetchProjectData(newProjectId);
```
