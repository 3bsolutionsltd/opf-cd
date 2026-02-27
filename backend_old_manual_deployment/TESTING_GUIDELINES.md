# Phase 5.4.5 Testing Guidelines

**Document Version:** 1.0  
**Date:** February 27, 2026  
**Scope:** Complete testing framework execution (76+ tests)  
**Audience:** QA Team, Developers, Project Manager  

---

## 📋 TABLE OF CONTENTS

1. [Pre-Testing Checklist](#pre-testing-checklist)
2. [Test Execution Phases](#test-execution-phases)
3. [How to Run Each Phase](#how-to-run-each-phase)
4. [Understanding Test Results](#understanding-test-results)
5. [Troubleshooting Guide](#troubleshooting-guide)
6. [Best Practices](#best-practices)
7. [Results Documentation](#results-documentation)
8. [Sign-Off Requirements](#sign-off-requirements)

---

## ✅ PRE-TESTING CHECKLIST

Before executing ANY tests, verify the following:

### Environment Setup
- [ ] Working directory: `c:\Users\DELL\opf-cd\backend_old_manual_deployment\`
- [ ] PHP version: 8.2+ (verify: `php -v`)
- [ ] Node.js installed (verify: `node -v`)
- [ ] npm installed (verify: `npm -v`)
- [ ] Composer dependencies installed (verify: `composer install` succeeded)
- [ ] npm packages installed (verify: `npm install` succeeded)

### Database Setup
- [ ] PostgreSQL server running and accessible
- [ ] Database `opf_cd` exists and is accessible
- [ ] Migrations have been executed: `php artisan migrate`
- [ ] ProjectTemplateSeeder has been executed: `php artisan db:seed --class=ProjectTemplateSeeder`
- [ ] Verify with: `php test_diagnostic.php` (should show ✓ for all items)

### Server Status
- [ ] Laravel development server running: `php artisan serve`
- [ ] Server accessible at: `http://127.0.0.1:8000`
- [ ] API endpoints responding: `curl http://127.0.0.1:8000/api/templates` (HTTP 200)
- [ ] No port conflicts (port 8000 should be available)

### Code Quality
- [ ] All test files exist:
  - `tests/Unit/Services/ProjectTemplateServiceTest.php`
  - `tests/Unit/Services/OpportunityProjectServiceTest.php`
  - `tests/Feature/TemplateApiTest.php`
  - `cypress/e2e/template-selection.cy.js`
- [ ] Service type hints are correct (Collection not Eloquent\Collection)
- [ ] Seeder is database-compatible (handles both PostgreSQL and SQLite)

### Dependencies
- [ ] PHPUnit installed: `.\vendor\bin\phpunit --version`
- [ ] Cypress installed: `npx cypress --version`
- [ ] All npm dependencies: `npm list` (should show cypress, etc.)
- [ ] Laravel artisan available: `php artisan --version`

**If any checklist item fails, resolve it BEFORE proceeding with tests.**

---

## 🎯 TEST EXECUTION PHASES

### Phase Overview

| Phase | Type | Tests | Duration | Command |
|-------|------|-------|----------|---------|
| 1 | Unit Tests | 20 tests | 5-10 min | `php artisan test tests/Unit/Services/` |
| 2 | Integration Tests | 13 tests | 5-10 min | `php artisan test tests/Feature/TemplateApiTest.php` |
| 3 | Manual API Tests | 13 endpoints | 30 min | `curl` commands |
| 4 | E2E Tests | 30+ tests | 45 min | `npx cypress run` |

### Recommended Execution Order

**Option A: Quick Validation (30 minutes)**
1. Phase 3: Manual API tests only
2. Verify 13/13 endpoints responding
3. Check performance < 200ms

**Option B: Full Suite (2-3 hours)**
1. Phase 1: Unit tests (5-10 min)
2. Phase 2: Integration tests (5-10 min)
3. Phase 3: Manual API tests (30 min)
4. Phase 4: E2E tests (45 min)
5. Review all results

**Option C: Recommended Hybrid (1.5 hours)**
1. Unit tests (Phase 1) - catches business logic issues
2. E2E tests (Phase 4) - catches UI/frontend issues  
3. Integration tests (Phase 2) - validates endpoints
4. Manual API tests (Phase 3) - final validation

---

## 🚀 HOW TO RUN EACH PHASE

### PHASE 1: Unit Tests (20 Tests)

**What's Being Tested:**
- ProjectTemplateService: Template CRUD, weight validation, task distribution
- OpportunityProjectService: Atomic project creation, transactions, error handling

**Command:**
```bash
cd c:\Users\DELL\opf-cd\backend_old_manual_deployment
php artisan test tests/Unit/Services/
```

**Alternative (Direct PHPUnit):**
```bash
.\vendor\bin\phpunit tests\Unit\Services\ --testdox
```

**Expected Output:**
```
PHPUnit 11.5.50 by Sebastian Bergmann and contributors.

Tests\Unit\Services\ProjectTemplateServiceTest
 ✓ Get all active templates returns collection
 ✓ Get template with tasks returns complete template
 ✓ Validate template weights passes for valid template
 ✓ Task weights sum to 100 for each template
 ✓ Correct task distribution for each template
 ✓ Total task count is 36
 ✓ Phase numbers are sequential per template
 ✓ All template tasks have required fields
 ✓ Foreign key relationships work
 ✓ Database queries use indexes

Tests\Unit\Services\OpportunityProjectServiceTest
 ✓ Create project with template creates project and tasks
 ✓ Created tasks have correct weights
 ✓ Transaction atomicity all succeed
 ✓ Rejects if opportunity not found
 ✓ Rejects if template not found
 ✓ Rejects if opportunity not won
 ✓ Audit trail created for project creation
 ✓ Apply template to empty project
 ✓ Apply template rejects project with tasks
 ✓ Performance project creation under 500ms

Time: 00:15.234, Memory: 42.00 MB

OK (20 tests, 45 assertions)
```

**Success Criteria:**
- All 20 tests pass (20/20)
- No errors or failures
- Execution time < 20 seconds
- Memory usage < 50MB

**If Tests Fail:**
1. Check error message carefully
2. Identify which specific test failed
3. Run that test individually: `php artisan test tests/Unit/Services/ProjectTemplateServiceTest.php --filter=test_name`
4. Review the test code to understand what it's checking
5. Check the actual code being tested for logic errors
6. Fix the issue and rerun

---

### PHASE 2: Integration Tests (13 Tests)

**What's Being Tested:**
- All 13 API endpoints
- HTTP status codes (200, 201, 404, 422, 400)
- Response structure validation
- Error handling

**Command:**
```bash
php artisan test tests/Feature/TemplateApiTest.php
```

**Alternative (Direct PHPUnit):**
```bash
.\vendor\bin\phpunit tests\Feature\TemplateApiTest.php --testdox
```

**Expected Output:**
```
Tests\Feature\TemplateApiTest
 ✓ Get templates returns all active
 ✓ Get template by id returns with tasks
 ✓ Preview template returns tasks
 ✓ Get invalid template returns 404
 ✓ Create project with template
 ✓ Create project validates input
 ✓ Create project rejects invalid template
 ✓ Apply template to project
 ✓ Apply template rejects project with tasks
 ✓ Api response times acceptable
 ✓ Admin get all templates
 ✓ Admin create template
 ✓ Malformed json request returns error

Time: 00:12.567, Memory: 48.00 MB

OK (13 tests, 28 assertions)
```

**Success Criteria:**
- All 13 tests pass (13/13)
- No errors or failures
- Execution time < 15 seconds
- All status codes correct

**If Tests Fail:**
1. Read the assertion error message
2. Check if server is running (`php artisan serve`)
3. Verify database has data: `php test_diagnostic.php`
4. Check specific endpoint with curl: `curl http://127.0.0.1:8000/api/templates`
5. Look at Laravel logs: `tail -f storage/logs/laravel.log`
6. Fix the issue and rerun

---

### PHASE 3: Manual API Tests (13 Endpoints)

**What's Being Tested:**
- Real HTTP requests to all endpoints
- Response status codes
- Response content validation
- Performance timing

**Simple Curl Test:**
```bash
# Test individual endpoints
curl http://127.0.0.1:8000/api/templates
curl http://127.0.0.1:8000/api/templates/1
curl http://127.0.0.1:8000/api/templates/1/preview
curl http://127.0.0.1:8000/api/admin/templates
```

**Automated Script (PowerShell):**
```powershell
cd c:\Users\DELL\opf-cd\backend_old_manual_deployment
.\test_api_endpoints.ps1
```

**Automated Script (Bash):**
```bash
bash test_api_endpoints.sh
```

**Expected Endpoints & Status Codes:**

| # | Method | Endpoint | Expected Status |
|---|--------|----------|-----------------|
| 1 | GET | /api/templates | 200 |
| 2 | GET | /api/templates/1 | 200 |
| 3 | GET | /api/templates/1/preview | 200 |
| 4 | GET | /api/templates/99999 | 404 |
| 5 | GET | /api/templates/1/tasks | 200 |
| 6 | POST | /api/opportunities/{opp_id}/projects/with-template | 201/200 |
| 7 | POST | /api/projects/{proj_id}/apply-template | 200 |
| 8 | GET | /api/admin/templates | 200/403 |
| 9 | POST | /api/admin/templates | 201/403 |
| 10 | PUT | /api/admin/templates/{id} | 200/403 |
| 11 | DELETE | /api/admin/templates/{id} | 204/403 |
| 12 | POST | /api/admin/templates/{id}/tasks | 201/403 |
| 13 | GET | /api/admin/templates/{id}/tasks | 200/403 |

**Success Criteria:**
- All 13 endpoints respond
- Correct HTTP status codes
- Response times < 200ms for GET, < 500ms for POST
- JSON responses parse without error
- No timeout errors

**If Endpoints Fail:**
1. Verify server is running: `Get-Process | grep artisan`
2. Test basic connectivity: `ping 127.0.0.1`
3. Check if port 8000 is in use: `netstat -ano | findstr :8000`
4. Verify database connection: `php test_diagnostic.php`
5. Check Laravel logs: `type storage/logs/laravel.log`
6. Restart server: Stop and run `php artisan serve` again

---

### PHASE 4: E2E Tests - Cypress (30+ Tests)

**What's Being Tested:**
- Template selection form (5 cards visible, correct data)
- Template preview modal (AJAX loading, displays tasks)
- Project creation workflow (form submission, redirect)
- Responsive design (mobile 375px, tablet 768px, desktop 1920px)
- Accessibility (ARIA labels, keyboard navigation)
- Admin dashboard workflows
- Error handling
- Console errors

**Command - Interactive Mode (Recommended for First Time):**
```bash
npx cypress open
```
Then select `template-selection.cy.js` and click "Run"
- Watch tests execute in the browser
- Easy to see what's failing visually
- Can pause and inspect elements

**Command - Headless Mode (Automated):**
```bash
npx cypress run --spec "cypress/e2e/template-selection.cy.js"
```

**Expected Output:**
```
 ✓ Should load the template selection page (850ms)
 ✓ Should display all 5 template cards (120ms)
 ✓ Should display correct template information (95ms)
 ✓ Template cards should have hover effects (80ms)
 ✓ Should open template preview modal (450ms)
 ✓ Preview modal should display tasks (200ms)
 ✓ Modal should close properly (100ms)
 ✓ Should handle ajax loading errors (350ms)
 ✓ Should validate form input (300ms)
 ✓ Form submission should create project (1200ms)
 ... (30+ total tests)

Passing: 30
Failing: 0
Pending: 0
Duration: 2m 15s
```

**Success Criteria:**
- All 30+ tests pass
- Zero console errors
- Zero uncaught exceptions
- Execution time < 3 minutes
- All assertions pass

**If E2E Tests Fail:**
1. Run in interactive mode to see what's failing: `npx cypress open`
2. Check browser console for JavaScript errors
3. Take screenshots: Cypress auto-captures on failure
4. Check if selectors exist: Verify `[data-testid="..."]` attributes in HTML
5. Verify server is running and responding
6. Clear browser cache: `npx cypress cache clear`
7. Check network tab for failed requests

---

## 📊 UNDERSTANDING TEST RESULTS

### Test Result Indicators

#### ✓ PASS
- Test executed successfully
- All assertions passed
- No errors or exceptions
- **Action:** Continue to next test

#### ✗ FAIL
- Test assertion failed
- One or more conditions not met
- **Action:** Review error message, fix code, rerun test

#### ⚠️ ERROR
- Test threw an exception
- Unexpected error occurred
- **Action:** Review stack trace, check dependencies, fix error, rerun test

#### ⊗ SKIP
- Test was skipped (marked with `.skip` or `.only`)
- Not executed
- **Action:** Remove skip modifier if intentional, rerun

### Understanding Failure Messages

**Example Unit Test Failure:**
```
1) Tests\Unit\Services\ProjectTemplateServiceTest::test_get_all_active_templates_returns_collection
   Expected: 5
   Actual: 0
   
Failed asserting that 0 equals 5.
```
**Meaning:** Service returned 0 templates instead of expected 5  
**Fix:** Check database has data, verify seeder ran: `php artisan db:seed --class=ProjectTemplateSeeder`

**Example Integration Test Failure:**
```
2) Tests\Feature\TemplateApiTest::test_get_templates_returns_all_active
   Expected status code 200 but received 500
   Response: {"message":"Unexpected Error"}
```
**Meaning:** API endpoint returned 500 error  
**Fix:** Check Laravel logs, verify database/service layer, restart server

**Example E2E Test Failure:**
```
3) template-selection.cy.js - Should display all 5 template cards
   Error: Timed out waiting for 5 elements with selector [data-testid="template-card"]
   Found: 0 elements
```
**Meaning:** Template cards not rendering in browser  
**Fix:** Check if page loaded, verify JavaScript executed, check for JavaScript errors

### Performance Baselines

| Operation | Target | Yellow Flag | Red Flag |
|-----------|--------|------------|----------|
| GET /api/templates | < 100ms | > 150ms | > 300ms |
| GET /api/templates/{id} | < 100ms | > 150ms | > 300ms |
| POST /api/projects/with-template | < 300ms | > 500ms | > 1000ms |
| Unit test execution | < 20s total | > 30s | > 45s |
| E2E test execution | < 3min total | > 4min | > 5min |

**Interpretation:**
- **Green (Under target):** Excellent, continue
- **Yellow (Yellow flag):** Monitor, may want to optimize
- **Red (Red flag):** Investigate and resolve

---

## 🔧 TROUBLESHOOTING GUIDE

### Problem: "Command 'php' not found"

**Cause:** PHP not in system PATH  
**Solution:**
```bash
# Option 1: Use full path
C:\php\php.exe artisan test

# Option 2: Add PHP to PATH (Windows)
# System Properties > Environment Variables > Add PHP directory to PATH

# Option 3: Use Docker
docker-compose up -d  # If docker-compose available
```

### Problem: "SQLSTATE[HY000]: General error: 1 near SET"

**Cause:** Seeder using PostgreSQL-specific `SET CONSTRAINTS` command on SQLite  
**Solution:**
```bash
# The seeder should handle this automatically, but if not:
# Edit: database/seeders/ProjectTemplateSeeder.php
# Ensure it checks database driver:
if (DB::connection()->getDriverName() === 'pgsql') {
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}
```

### Problem: "Connection refused" (Laravel server not responding)

**Cause:** Server not running or wrong port  
**Solution:**
```bash
# Start server
php artisan serve

# Or with custom port
php artisan serve --port=8000

# Verify running
curl http://127.0.0.1:8000/api/templates
```

### Problem: "No such file or directory: tests/Unit/..."

**Cause:** Wrong working directory  
**Solution:**
```bash
# Verify correct directory
cd c:\Users\DELL\opf-cd\backend_old_manual_deployment
pwd  # Should show backend_old_manual_deployment

# Then run tests
php artisan test tests/Unit/Services/
```

### Problem: "Column 'project_templates.is_active' doesn't exist"

**Cause:** Migrations not executed  
**Solution:**
```bash
# Run migrations
php artisan migrate

# Or manually
php run_migration_017.php

# Verify with diagnostic
php test_diagnostic.php
```

### Problem: "Timeout waiting for element [data-testid=...]"

**Cause:** Element not rendered or JavaScript failed  
**Solution:**
```bash
# Run in interactive mode to see what's happening
npx cypress open

# Check browser console for errors
# Take screenshot on failure: stored in cypress/screenshots/

# Verify server is running
curl http://127.0.0.1:8000/

# Clear cache
npx cypress cache clear
npm cache clean --force
```

### Problem: "Error: listen EADDRINUSE: address already in use :::8000"

**Cause:** Port 8000 already in use  
**Solution:**
```bash
# Find process using port 8000
netstat -ano | findstr :8000

# Kill process (replace PID with actual process ID)
taskkill /PID 1234 /F

# Or use different port
php artisan serve --port=8001

# Test on new port
curl http://127.0.0.1:8001/api/templates
```

### Problem: Tests pass locally but fail in CI/CD

**Cause:** Environment differences (database, PHP version, etc.)  
**Solution:**
1. Verify PHP version: `php -v` (should be 8.2+)
2. Verify database environment variables in `.env`
3. Verify all dependencies installed: `composer install`, `npm install`
4. Check CI/CD environment has same PHP version
5. Run `php test_diagnostic.php` in CI/CD environment

---

## ✨ BEST PRACTICES

### 1. Run Tests in Order
```
Phase 1 (Unit) → Phase 2 (Integration) → Phase 3 (Manual) → Phase 4 (E2E)
```
Early phases catch fundamental issues before later phases run.

### 2. One Phase at a Time
Don't run all phases simultaneously. Run:
1. Phase 1 completely
2. Fix any failures
3. Run Phase 2
4. Fix any failures
5. Continue...

### 3. Read Error Messages Carefully
```
✗ FAIL
Description: "Template weights don't sum to 100"
Expected: 100
Actual: 99
Expected weights: [20, 30, 25, 25]
Issue: Last weight should be 25, got 24
```

The error message tells you exactly what's wrong.

### 4. Document Results As You Go

Create a test execution log:
```
Date: 2026-02-27
Tester: [Name]
Environment: Development (PostgreSQL)

PHASE 1: Unit Tests
  Start: 14:30
  Status: PASS (20/20)
  Duration: 12 seconds
  Notes: All tests passed without issues
  
PHASE 2: Integration Tests
  Start: 14:45
  Status: PASS (13/13)
  Duration: 10 seconds
  Notes: All endpoints responding correctly
  
...etc
```

### 5. Test Consistently

Always test in the same order using the same scripts:
```bash
# Define standard test sequence
./test_phase1.sh  # Unit tests
./test_phase2.sh  # Integration tests
./test_phase3.sh  # Manual API
./test_phase4.sh  # E2E
```

### 6. Capture Screenshots on Failure
```bash
# E2E tests auto-capture on failure
# Check: cypress/screenshots/template-selection.cy.js/

# For manual testing, take screenshots manually
# When error occurs, take screenshot and save with error description
```

### 7. Test in Clean Environment
```bash
# Before each test run:
# 1. Restart Laravel server
php artisan serve

# 2. Clear cache
php artisan cache:clear

# 3. Verify database
php test_diagnostic.php
```

### 8. Test Incrementally
Don't wait until all tests are done. After each phase:
1. Review results
2. Document findings
3. Fix issues immediately
4. Proceed to next phase

### 9. Version Control

Document test runs in git:
```bash
git add TESTING_LOG_2026-02-27.md
git commit -m "Testing: Phase 1-2 complete, all 33 tests passing"
```

### 10. Communicate Progress

Keep team informed:
- "Phase 1 complete: 20/20 passing"
- "Phase 2 complete: 13/13 passing"
- "3 E2E tests failing, investigating..."
- "All tests complete. Ready for deployment."

---

## 📝 RESULTS DOCUMENTATION

### Test Execution Log Template

```markdown
# Test Execution Log - 2026-02-27

## Summary
- Date: 2026-02-27
- Tester: [Name]
- Environment: Development
- Total Tests: 76+
- Total Duration: 2h 45m
- Overall Result: PASS/FAIL

## Phase 1: Unit Tests
- Tests Count: 20
- Passed: 20
- Failed: 0
- Duration: 12 seconds
- Issues: None
- Fixed: N/A

## Phase 2: Integration Tests
- Tests Count: 13
- Passed: 13
- Failed: 0
- Duration: 10 seconds
- Issues: None
- Fixed: N/A

## Phase 3: Manual API Tests
- Endpoints: 13
- Responding: 13
- Failed: 0
- Duration: 30 minutes
- Average Response Time: 12ms
- Issues: None

## Phase 4: E2E Tests
- Tests Count: 30+
- Passed: 30
- Failed: 0
- Duration: 2m 15s
- Console Errors: 0
- Issues: None

## Issues Found
None

## Fixes Applied
None

## Sign-Off
- Tester: [Name]
- Date: 2026-02-27
- Status: APPROVED FOR PRODUCTION
```

### For Each Failure, Document:

```
FAILURE REPORT

Issue #: [Number]
Phase: [Phase name]
Test: [Test name]
Status: FAILED

Expected Behavior:
[What should happen]

Actual Behavior:
[What actually happened]

Error Message:
[Exact error text]

Root Cause:
[Why this happened]

Fix Applied:
[What was changed to fix it]

Re-test Result:
[Did the fix work?]

Date Fixed: [Date]
Fixed By: [Name]
Verified By: [Name]
```

---

## ✍️ SIGN-OFF REQUIREMENTS

### Pre-Launch Sign-Off Checklist

**For QA Lead:**
- [ ] All 76+ tests executed
- [ ] All tests passed (or failures documented and fixed)
- [ ] Performance benchmarks met
- [ ] No critical bugs found
- [ ] No security issues identified
- [ ] Test documentation complete
- [ ] Sign-off: _________________ Date: _______

**For Developer:**
- [ ] All code reviewed
- [ ] All failures fixed
- [ ] All fixes tested and verified
- [ ] No regressions introduced
- [ ] Code quality maintained
- [ ] Sign-off: _________________ Date: _______

**For Product Manager:**
- [ ] Business requirements met
- [ ] All features working as expected
- [ ] Performance acceptable
- [ ] User experience validated
- [ ] Ready for production deployment
- [ ] Sign-off: _________________ Date: _______

### Launch Approval Criteria

Before marking Phase 5.4.5 complete:
- ✅ All 76 tests passing (or failures resolved)
- ✅ Zero console errors in E2E tests
- ✅ Performance within targets
- ✅ All critical bugs fixed
- ✅ All stakeholders signed off
- ✅ Deployment plan in place
- ✅ Rollback procedure documented

---

## 🎯 QUICK REFERENCE COMMANDS

```bash
# Setup
php test_diagnostic.php
php artisan migrate
php artisan db:seed --class=ProjectTemplateSeeder

# Start Server
php artisan serve

# Phase 1: Unit Tests
php artisan test tests/Unit/Services/

# Phase 2: Integration Tests
php artisan test tests/Feature/TemplateApiTest.php

# Phase 3: Manual API
curl http://127.0.0.1:8000/api/templates
.\test_api_endpoints.ps1

# Phase 4: E2E
npx cypress open
npx cypress run --spec "cypress/e2e/template-selection.cy.js"

# Utilities
php artisan cache:clear
php artisan tinker
git log --oneline -5
```

---

## 📞 SUPPORT & ESCALATION

### If Tests Fail:

1. **Check error message** - Most errors are self-explanatory
2. **Run diagnostic** - `php test_diagnostic.php`
3. **Review test code** - Understand what's being tested
4. **Check application code** - Verify the code being tested
5. **Check logs** - `tail -f storage/logs/laravel.log`
6. **Consult troubleshooting guide** - Above in this document
7. **Escalate if needed** - Contact development team

### Expected Resolution Timeline:

- Unit test failure: 15-30 minutes
- Integration test failure: 30 minutes - 1 hour
- E2E test failure: 30 minutes - 2 hours
- API endpoint failure: 1-2 hours
- Performance issue: 2-4 hours

---

*Last Updated: February 27, 2026*  
*Next Review: After Phase 5.4.5 completion*  
*Document Owner: QA Lead*
