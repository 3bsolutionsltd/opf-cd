# Payment Recording Flow

## Overview

The OPF-CD system ensures data integrity by **preventing direct milestone status changes to 'paid'**. 
All payments must be recorded through the dedicated payment recording endpoint, which atomically:
1. Creates a `cash_transactions` record
2. Updates the milestone status to 'paid'

This ensures that analytics dashboards always have accurate cash flow data.

---

## Why This Matters

**Problem**: If milestones are marked as 'paid' without creating corresponding cash transactions, analytics will show incorrect data:
- Cash flow will be understated (missing inflow)
- Payment gap calculations will be wrong (earned value > received value)
- Project health scores will be inaccurate

**Solution**: The system now enforces that milestone status can only be changed to 'paid' through the payment recording service.

---

## How to Record a Payment

### API Endpoint

```
POST /api/milestones/{milestoneId}/record-payment
```

**Required Parameters:**
- `account_id` (integer): The account that received the payment
- `transaction_date` (string): Date payment was received (YYYY-MM-DD format)

**Response** (Success):
```json
{
  "success": true,
  "message": "Payment received: USD 5000 recorded for milestone 'First Payment' (ID: 3)",
  "transaction_id": 10,
  "milestone_id": 3,
  "account_id": 5,
  "amount": 5000.0,
  "currency": "USD",
  "transaction_date": "2026-02-07"
}
```

**Response** (Error):
```json
{
  "success": false,
  "message": "Payment milestone #3 is already marked as paid. Cannot record payment twice.",
  "transaction_id": null
}
```

---

## Validation Rules

The payment recording service performs **6 validation checks** before writing any data:

1. **Milestone exists**: The milestone ID must be valid
2. **Not already paid**: Prevents duplicate payments (idempotent)
3. **Account exists**: The account ID must be valid
4. **Currency match**: Milestone currency must match account currency
5. **Date format**: Transaction date must be in YYYY-MM-DD format
6. **No existing transaction**: Defense-in-depth check for data integrity

If **any** validation fails, the operation is rejected and **no data is written**.

---

## Permissions

The payment recording endpoint requires the `milestones:edit` permission.

Route protection:
```php
Route::middleware(['check.permission:milestones,edit'])->group(function () {
    Route::post('/milestones/{milestoneId}/record-payment', [MilestoneController::class, 'recordPayment']);
});
```

---

## What Changed

### Before (Unsafe)

Milestones could be updated directly with `status='paid'`:

```json
PUT /api/milestones/3
{
  "status": "paid"
}
```

**Problem**: No cash_transaction created → analytics broken

### After (Safe)

Direct status changes to 'paid' are **blocked**:

```json
PUT /api/milestones/3
{
  "status": "paid"
}

→ Returns error: "Cannot mark milestone as paid directly. Use the payment recording form."
```

Must use dedicated endpoint:

```json
POST /api/milestones/3/record-payment
{
  "account_id": 5,
  "transaction_date": "2026-02-07"
}

→ Creates cash_transaction + updates milestone status atomically
```

---

## Database Schema

### Polymorphic Link

Cash transactions link to milestones using a **polymorphic relationship**:

```sql
SELECT * FROM cash_transactions
WHERE source_type = 'payment_milestone'
  AND source_id = 3;
```

This allows the `cash_transactions` table to track cash from multiple sources (project payments, expense payments, etc.) using a single table.

---

## Historical Data Migration

For milestones that were marked 'paid' before this change (orphaned milestones with no transaction), use the migration command:

```bash
php artisan payments:migrate-historical --milestone-ids=18 --account-id=5
```

Options:
- `--milestone-ids=18,19,20`: Specific milestones to migrate
- `--account-id=5`: Which account to record the payments in
- `--dry-run`: Preview changes without writing
- `--interactive`: Confirm each milestone individually

See: `app/Console/Commands/MigrateHistoricalPayments.php`

---

## Testing

### Service Tests

```bash
php artisan test --filter ReceiveProjectPaymentServiceTest
```

Tests cover:
- ✅ Successful payment recording
- ✅ Duplicate prevention
- ✅ Currency validation
- ✅ Date format validation
- ✅ Idempotency (can't pay twice)

### Prevention Tests

```bash
php artisan test --filter MilestoneManagementServiceTest
```

Tests cover:
- ✅ Blocks direct marking as 'paid'
- ✅ Allows updating to 'invoiced'
- ✅ Prevents editing paid milestones

---

## Related Files

**Core Services:**
- `app/Services/ReceiveProjectPaymentService.php` - Payment recording logic
- `app/Services/MilestoneManagementService.php` - Milestone CRUD with prevention

**Controllers:**
- `app/Http/Controllers/MilestoneController.php` - API endpoints

**Validation:**
- `app/Http/Requests/RecordPaymentRequest.php` - Payment recording validation
- `app/Http/Requests/UpdateMilestoneRequest.php` - Milestone update validation

**Analytics Services:**
- `app/Services/CashFlowService.php` - Reads from cash_transactions
- `app/Services/PaymentGapService.php` - Compares earned vs received
- `app/Services/ProjectHealthService.php` - Overall project health score

**Migration:**
- `app/Console/Commands/MigrateHistoricalPayments.php` - Fix legacy data

**Tests:**
- `tests/Unit/Services/ReceiveProjectPaymentServiceTest.php`
- `tests/Unit/Services/MilestoneManagementServiceTest.php`

---

## Domain Rules (from _truth.md)

- **Work progress ≠ payment received** (tracked separately)
- **Financial records are immutable after posting** (milestone.status='paid' cannot be changed)
- **All calculations are deterministic and explainable** (cash_transactions is source of truth)
- **No automatic inference** (cash must be explicitly recorded)

---

## Summary

✅ **Data Integrity Enforced**: Milestones can only be marked 'paid' through payment recording
✅ **Account Capture Required**: System knows which account received each payment
✅ **Atomic Operations**: Both cash_transaction and milestone update happen together or not at all
✅ **Analytics Accuracy**: Dashboards always show correct cash flow and payment gap
✅ **Idempotent**: Safe to retry - duplicate payments prevented
✅ **Tested**: Comprehensive test coverage for all scenarios
✅ **Migration Tool**: Can fix historical data if needed

**Result**: Payment data integrity is now guaranteed across the entire system.
