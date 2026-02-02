# OPF-CD API Contracts (LOCKED)

These contracts are FINAL.
No service, controller, or frontend may alter shapes or add wrappers.

---

## Projects

### GET /projects/{id}/progress
Response:
<number>  // float 0–100

Example:
67.5

---

### GET /projects/{id}/payment-gap
Response:
{
  "gap_amount": number,
  "gap_percentage": number,
  "progress": number,
  "earned_value": number,
  "received_value": number,
  "contract_value": number
}

---

### GET /projects/{id}/health
Response:
{
  "project_id": number,
  "health_status": "green" | "amber" | "red",
  "score": number,
  "signals": object,
  "reasons": string[]
}

---

## Finance

### GET /finance/cash-flow
Response:
{
  "cash_at_hand": number,
  "total_inflows": number,
  "total_outflows": number,
  "net_cash_flow": number,
  "average_monthly_burn": number,
  "cash_runway_months": number | null
}

---

### GET /finance/expenses/upcoming
Response:
Array<{
  "expense_id": number,
  "name": string,
  "category": string,
  "amount": number,
  "currency": string,
  "due_date": string (YYYY-MM-DD),
  "type": "one_off" | "recurring",
  "source": "original" | "projected"
}>

---

## Sales

### GET /sales/pipeline
Response:
{
  "total_pipeline_value": number,
  "weighted_pipeline_value": number,
  "opportunity_count": number,
  "by_stage": Array<{
    "stage": string,
    "count": number,
    "total_value": number,
    "weighted_value": number
  }>
}
