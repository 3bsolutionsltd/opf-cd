# Historical Payment Migration

## Problem

Before `ReceiveProjectPaymentService` was implemented, payments may have been recorded by:
- Directly updating `payment_milestones.status = 'paid'`
- Creating `cash_transactions` without linking to milestones
- Manual database updates

This creates **orphaned data**:
- ✗ Paid milestones without corresponding cash_transactions
- ✗ Dashboards showing incorrect cash flow
- ✗ Inability to retrieve payment receipts

## Solution

The `MigrateHistoricalPayments` command creates proper `cash_transactions` records for all paid milestones that lack them.

---

## Pre-Migration Checklist

### 1. Verify Orphaned Payments Exist

```sql
SELECT 
    pm.id,
    pm.description,
    pm.amount,
    pm.currency,
    pm.status,
    p.name as project_name
FROM payment_milestones pm
LEFT JOIN cash_transactions ct ON ct.payment_milestone_id = pm.id
JOIN projects p ON p.id = pm.project_id
WHERE pm.status = 'paid' 
  AND ct.id IS NULL;
```

If this returns rows, you have orphaned payments.

### 2. Ensure Accounts Exist

```sql
SELECT id, account_name, account_type, currency 
FROM accounts 
ORDER BY account_name;
```

You'll need at least one account to record historical transactions.

### 3. Backup Database

```bash
pg_dump opf_cd > backup_before_payment_migration.sql
```

---

## Running the Migration

### Dry Run (Preview Changes)

See what would be migrated without making changes:

```bash
php artisan payments:migrate-historical --dry-run
```

**Output:**
```
=== Historical Payment Migration ===

Found 5 paid milestone(s) without cash_transactions:

┌────┬──────────────┬─────────────────┬──────────┬──────────┬────────┐
│ ID │ Project      │ Description     │ Amount   │ Currency │ Status │
├────┼──────────────┼─────────────────┼──────────┼──────────┼────────┤
│ 12 │ Website Dev  │ Milestone 1     │ 5000.00  │ USD      │ paid   │
│ 15 │ Mobile App   │ Initial Payment │ 10000.00 │ UGX      │ paid   │
└────┴──────────────┴─────────────────┴──────────┴──────────┴────────┘

Select account for historical transactions:
┌────┬──────────────────┬──────────┬──────────┐
│ ID │ Account Name     │ Type     │ Currency │
├────┼──────────────────┼──────────┼──────────┤
│ 1  │ Main UGX Account │ bank     │ UGX      │
│ 2  │ USD Operations   │ bank     │ USD      │
└────┴──────────────────┴──────────┴──────────┘
```

### Execute Migration (Live)

**Option 1: Interactive** (prompts for account selection)

```bash
php artisan payments:migrate-historical
```

**Option 2: Non-interactive** (specify account)

```bash
php artisan payments:migrate-historical --account-id=1
```

---

## What the Command Does

### For Each Orphaned Milestone:

1. **Validates Currency**
   - Ensures account currency matches milestone currency
   - Prevents currency mismatch errors

2. **Creates Cash Transaction**
   ```php
   cash_transactions {
     account_id: <selected_account>
     transaction_type: 'inflow'
     amount: <milestone_amount>
     currency: <milestone_currency>
     transaction_date: <milestone_updated_at or invoice_date>
     payment_milestone_id: <milestone_id>
     description: "Historical payment: <milestone_description>"
   }
   ```

3. **Verifies Integrity**
   - Confirms milestone status is still 'paid'
   - Uses database transaction (atomic operation)
   - Rolls back on any error

### Safety Features

✓ **Idempotent**: Safe to run multiple times (only processes orphans)  
✓ **Atomic**: Uses DB transactions (all-or-nothing per milestone)  
✓ **Validated**: Checks currency matching before insertion  
✓ **Auditable**: Marks transactions as "Historical payment" in description  

---

## Post-Migration Verification

### 1. Verify No Orphans Remain

```sql
SELECT COUNT(*) 
FROM payment_milestones pm
LEFT JOIN cash_transactions ct ON ct.payment_milestone_id = pm.id
WHERE pm.status = 'paid' AND ct.id IS NULL;
-- Should return 0
```

### 2. Test Service Methods

```php
use App\Services\ReceiveProjectPaymentService;

$service = new ReceiveProjectPaymentService();

// For each migrated milestone
$receipt = $service->getPaymentReceipt($milestoneId);
// Should now return transaction details (not null)

$isPaid = $service->isPaid($milestoneId);
// Should return true
```

### 3. Verify Dashboard Data

Check that cash flow dashboards now include historical payments:

```sql
SELECT 
    SUM(amount) as total_inflows,
    currency
FROM cash_transactions
WHERE transaction_type = 'inflow'
GROUP BY currency;
```

---

## Common Scenarios

### Scenario 1: Multiple Currencies

If you have milestones in different currencies:

```bash
# Migrate USD payments to USD account
php artisan payments:migrate-historical --account-id=2

# Then migrate UGX payments to UGX account  
php artisan payments:migrate-historical --account-id=1
```

The command will **skip** milestones with currency mismatch and report errors.

### Scenario 2: Unknown Transaction Dates

The command uses `milestone.updated_at` as the transaction date. If `updated_at` is null, it falls back to `invoice_date`.

If both are problematic, manually update `updated_at` before migration:

```sql
UPDATE payment_milestones
SET updated_at = invoice_date
WHERE status = 'paid' AND updated_at IS NULL;
```

### Scenario 3: Migration Interrupted

If the command fails mid-way:
1. Check the error message
2. Fix the issue (e.g., add missing account)
3. Re-run the command (it's idempotent)

---

## Troubleshooting

### Error: "No accounts found"

**Solution:** Create at least one account:

```sql
INSERT INTO accounts (account_name, account_type, currency, balance, created_at, updated_at)
VALUES ('Historical Payments Account', 'bank', 'USD', 0, NOW(), NOW());
```

### Error: "Currency mismatch"

**Cause:** Selected account currency doesn't match milestone currency.

**Solution:** Use correct account or create one with matching currency.

### Error: "Milestone status changed during migration"

**Cause:** Another process modified the milestone while migration was running.

**Solution:** Re-run the command (it will pick up the milestone again if still orphaned).

---

## Best Practices

### Before Production Use

1. **Run dry-run** to preview changes
2. **Backup database** before live migration
3. **Test on staging** environment first
4. **Verify post-migration** with SQL queries

### After Migration

1. **Document** which account was used for historical transactions
2. **Notify finance team** that historical data now appears in dashboards
3. **Archive migration logs** for audit trail
4. **Update** any financial reports that may have excluded historical payments

---

## Integration with ReceiveProjectPaymentService

After migration, all methods work correctly:

```php
$service = new ReceiveProjectPaymentService();

// ✓ Returns true for migrated payments
$service->isPaid($milestoneId);

// ✓ Returns transaction details
$service->getPaymentReceipt($milestoneId);

// ✓ Prevents re-recording (idempotency)
$result = $service->receive($milestoneId, $accountId, '2026-02-06');
// Returns: ['success' => true, 'message' => 'Payment already recorded']
```

---

## Summary

| Feature | Status |
|---------|--------|
| Dry-run mode | ✓ |
| Interactive account selection | ✓ |
| Non-interactive (--account-id) | ✓ |
| Currency validation | ✓ |
| Atomic transactions | ✓ |
| Idempotent | ✓ |
| Error handling | ✓ |
| Audit trail | ✓ |

The migration command safely reconciles historical payment data with the new service-based approach, ensuring data integrity across the entire system.
