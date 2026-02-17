# Security Documentation

This document outlines the security measures, validation rules, and testing guidelines implemented in the OPF Capital Dashboard application.

## Table of Contents

1. [Input Validation](#input-validation)
2. [Rate Limiting](#rate-limiting)
3. [Authentication & Authorization](#authentication--authorization)
4. [Database Security](#database-security)
5. [Testing Guidelines](#testing-guidelines)
6. [Security Best Practices](#security-best-practices)

---

## Input Validation

All user input is validated using Laravel FormRequest classes before reaching controllers. This prevents SQL injection, XSS attacks, and invalid data from entering the system.

### Validation Classes

#### CreateProjectRequest
**Path:** `backend/app/Http/Requests/CreateProjectRequest.php`

**Rules:**
- `name` - required, string, max 255 characters
- `client_name` - required, string, max 255 characters
- `start_date` - required, date format (Y-m-d), not after end_date
- `end_date` - required, date format (Y-m-d), not before start_date
- `contract_value` - required, numeric, min 0
- `currency` - required, in:USD,UGX
- `status` - required, in:active,completed,on-hold

**Prevents:** Invalid dates, negative contract values, unsupported currencies

---

#### CreateExpenseRequest
**Path:** `backend/app/Http/Requests/CreateExpenseRequest.php`

**Rules:**
- `description` - required, string, max 500 characters
- `amount` - required, numeric, min 0.01
- `currency` - required, in:USD,UGX
- `type` - required, in:operational,project-related
- `due_date` - required, date format (Y-m-d)
- `status` - required, in:due,paid
- `project_id` - nullable, integer, exists in projects table

**Prevents:** Zero or negative amounts, invalid expense types, references to non-existent projects

---

#### CreateCashTransactionRequest
**Path:** `backend/app/Http/Requests/CreateCashTransactionRequest.php`

**Rules:**
- `account_id` - required, integer, exists in accounts table
- `type` - required, in:inflow,outflow
- `amount` - required, numeric, min 0.01
- `category` - required, string, max 100 characters
- `description` - required, string, max 500 characters
- `date` - required, date format (Y-m-d)

**Prevents:** Invalid transaction types, references to non-existent accounts, backdated transactions without proper validation

---

#### CreateOpportunityRequest
**Path:** `backend/app/Http/Requests/CreateOpportunityRequest.php`

**Rules:**
- `name` - required, string, max 255 characters
- `value` - required, numeric, min 0
- `currency` - required, in:USD,UGX
- `stage` - required, in:lead,qualified,proposal,negotiation,won,lost
- `close_probability` - required, integer, min 0, max 100
- `expected_close_date` - required, date format (Y-m-d)

**Prevents:** Invalid probability values (outside 0-100 range), unsupported sales stages

---

#### CreateAccountRequest
**Path:** `backend/app/Http/Requests/CreateAccountRequest.php`

**Rules:**
- `name` - required, string, max 255 characters
- `type` - required, in:bank,mobile_money,cash
- `currency` - required, in:USD,UGX
- `opening_balance` - required, numeric, min 0

**Prevents:** Negative opening balances, invalid account types

---

## Rate Limiting

API endpoints are protected against abuse using Laravel's built-in throttling middleware.

### Configuration
**File:** `backend/bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('60,1');  // 60 requests per minute per user
})
```

### Protected Routes
All routes under `/api/*` are rate-limited to:
- **60 requests per minute per authenticated user**
- **429 Too Many Requests** status returned when limit exceeded

### Bypass for Testing
Rate limiting is automatically disabled during automated testing (detected via `APP_ENV=testing`)

---

## Authentication & Authorization

### Authentication
All API endpoints require authentication via Laravel Sanctum or session-based authentication.

**Middleware:** `auth:api`

### Authorization
Permission checks are enforced using the Permission Model:
- **Resources:** projects, expenses, opportunities, dashboards, accounts, etc.
- **Actions:** view, create, edit, delete
- **Mapping:** User → Role → Permissions

**Example:**
```php
// User must have 'view' permission for 'dashboards' resource
if (!$user->hasPermission('dashboards', 'view')) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

### Protected Endpoints
- `GET /api/dashboard/summary` - Requires `dashboards:view`
- `GET /api/alerts` - Requires `dashboards:view`
- `GET /api/audit-logs` - Requires `dashboards:view`
- `GET /api/reports/export/*` - Requires `dashboards:view`

---

## Database Security

### SQL Injection Prevention
All database queries use:
1. **Eloquent ORM** - Automatic parameter binding
2. **Query Builder** - Parameterized queries with `?` placeholders
3. **Prepared Statements** - Never concatenate user input into SQL

**Safe Pattern:**
```php
DB::table('projects')->where('status', $status)->get();
```

**Unsafe Pattern (NEVER DO THIS):**
```php
DB::raw("SELECT * FROM projects WHERE status = '$status'");  // VULNERABLE
```

### Mass Assignment Protection
All models define `$fillable` or `$guarded` properties to prevent mass assignment vulnerabilities.

**Example:**
```php
protected $fillable = ['name', 'client_name', 'start_date', 'end_date'];
```

### Audit Trail
All CRUD operations are logged to the `audit_logs` table with:
- User ID
- Action (create, update, delete)
- Entity type and ID
- Old and new values (JSON)
- IP address and user agent
- Timestamp (immutable)

**File:** `backend/app/Services/AuditService.php`

---

## Testing Guidelines

### Test Organization
```
backend/tests/
├── Unit/              # Service layer tests (business logic)
│   └── Services/
│       ├── CashFlowServiceTest.php
│       ├── ProjectHealthServiceTest.php
│       └── AlertServiceTest.php
└── Feature/           # API integration tests (full request/response)
    └── Api/
        ├── DashboardApiTest.php
        ├── AlertApiTest.php
        ├── AuditLogApiTest.php
        └── ReportExportApiTest.php
```

### Unit Tests
**Purpose:** Test business logic in isolation without HTTP layer

**Pattern:**
```php
use RefreshDatabase;

protected function setUp(): void
{
    parent::setUp();
    // Create test data
}

public function it_calculates_cash_runway_correctly()
{
    // Arrange: Set up test data
    // Act: Call service method
    // Assert: Verify result
}
```

**Coverage:**
- Cash flow calculations (cash at hand, burn rate, runway)
- Project Health Index (PHI) weighted scoring
- Alert generation logic (5 alert types)
- Date-based calculations (overdue detection)

### Integration Tests
**Purpose:** Test full API request/response cycle with authentication

**Pattern:**
```php
public function it_returns_dashboard_summary_with_correct_structure()
{
    $response = $this->actingAs($user)
        ->getJson('/api/dashboard/summary?currency=USD');
    
    $response->assertStatus(200)
        ->assertJsonStructure(['cash_at_hand', 'burn_rate', ...]);
}
```

**Coverage:**
- Authentication enforcement (401 for unauthenticated)
- Response structure validation
- Data accuracy (matches business logic)
- Filtering and pagination
- Error handling (404, 422, 500)

### Running Tests
```bash
# All tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test class
php artisan test --filter=DashboardApiTest

# With coverage report
php artisan test --coverage
```

### Test Database
Tests use in-memory SQLite database via `RefreshDatabase` trait:
- Clean database for each test
- Fast execution
- No contamination between tests

**Configuration:** `phpunit.xml`

---

## Security Best Practices

### 1. Never Trust User Input
- **Always validate** using FormRequest classes
- **Always sanitize** output to prevent XSS
- **Always use parameterized queries** to prevent SQL injection

### 2. Principle of Least Privilege
- Users should only have permissions they need
- Default role should have minimal permissions
- Regularly audit user permissions

### 3. Secure Configuration
- Use `.env` file for sensitive configuration
- Never commit `.env` to version control
- Use strong, unique `APP_KEY` in production

### 4. HTTPS Only in Production
- Force HTTPS in production environment
- Set `Secure` flag on cookies
- Use HSTS headers

### 5. Regular Security Updates
- Keep Laravel framework updated
- Monitor `composer audit` for vulnerable dependencies
- Subscribe to Laravel security advisories

### 6. Error Handling
- Never expose stack traces in production
- Log errors to secure location
- Return generic error messages to users

**Production Configuration:**
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
```

### 7. Session Security
- Use `SameSite=Strict` cookie attribute
- Regenerate session ID after login
- Implement session timeout (default: 120 minutes)

### 8. Password Security
- Minimum 8 characters enforced
- Use bcrypt hashing (Laravel default)
- Implement password reset with expiring tokens

---

## Incident Response

### If Security Issue Discovered

1. **Assess Impact**
   - Identify affected systems
   - Determine data exposure
   - Estimate user impact

2. **Contain Breach**
   - Disable affected endpoint if needed
   - Rotate compromised credentials
   - Block malicious IPs

3. **Fix Vulnerability**
   - Patch code immediately
   - Deploy to production ASAP
   - Run full test suite

4. **Notify Stakeholders**
   - Inform management
   - Notify affected users if data exposed
   - Document incident

5. **Post-Mortem**
   - Analyze root cause
   - Update security procedures
   - Add regression tests

### Reporting Security Issues
**Email:** security@opfcapital.com (if applicable)
**Process:** Responsible disclosure with 90-day embargo

---

## Compliance

### Data Protection
- User data stored encrypted at rest
- PII (Personally Identifiable Information) minimized
- User can request data deletion

### Audit Requirements
- All financial transactions logged (immutable)
- User actions tracked with timestamps
- Logs retained for 7 years (configurable)

### Backup Security
- Encrypted backups stored offsite
- Regular backup testing
- Access restricted to authorized personnel

---

## Changelog

| Date       | Change                                      | Reference         |
|------------|---------------------------------------------|-------------------|
| 2024-01-XX | Initial security documentation              | Sprint 6          |
| 2024-01-XX | Added FormRequest validation classes        | Sprint 6          |
| 2024-01-XX | Implemented rate limiting (60 req/min)      | Sprint 6          |
| 2024-01-XX | Created comprehensive test suite            | Sprint 6          |

---

## Additional Resources

- [Laravel Security Documentation](https://laravel.com/docs/10.x/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://github.com/Snipe/laravel-best-practices-security)
- Internal: `docs/domain_guardrails.md`
- Internal: `docs/backend/05-permissions.md`
