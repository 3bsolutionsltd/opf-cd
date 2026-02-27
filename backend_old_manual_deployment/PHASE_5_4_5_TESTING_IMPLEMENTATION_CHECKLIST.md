# Phase 5.4.5: Testing & Launch Implementation Checklist

**Phase Status:** Ready to Execute  
**Estimated Duration:** 3-4 days  
**Target Launch Date:** February 28-29, 2026  
**Prepared By:** Development Team  

---

## 📋 IMPLEMENTATION ROADMAP

### WEEK 1: Database & Service Testing (Day 1-2)

#### Day 1: Database Schema Validation

```bash
# 1. Verify migrations exist
ls -la database/migrations/
# Expected: *.php files for 017 and 018

# 2. Run migrations on test database
php artisan migrate --database=testing

# 3. Verify tables created with correct structure
php artisan tinker
# > Schema::getConnection()->getDoctrineSchemaManager()->listTableNames()
# Expected: [..., 'project_templates', 'project_template_tasks']

# 4. Show table structure
php artisan tinker
# > Schema::getColumnListing('project_templates')
# > Schema::getColumnListing('project_template_tasks')
```

**Checklist:**
- [ ] Migration 017 creates `project_templates` table successfully
- [ ] Migration 018 creates `project_template_tasks` table successfully
- [ ] Migration adds `project_type`, `auto_apply_template`, `suggested_template_id` to `opportunities`
- [ ] All foreign keys created correctly
- [ ] All indexes created for performance
- [ ] No migration errors in test environment

#### Day 1: Seeder Validation

```bash
# 1. Run ProjectTemplateSeeder
php artisan db:seed --class=ProjectTemplateSeeder

# 2. Verify data in database
php artisan tinker
# > DB::table('project_templates')->count()
# Expected: 5

# > DB::table('project_templates')->get()
# Expected: Web App, Mobile App, E-Commerce, Integration, Maintenance

# > DB::table('project_template_tasks')->count()
# Expected: 36 (5+7+8+9+7)

# 3. Verify task distribution
# > DB::table('project_template_tasks')
#     ->selectRaw('project_template_id, SUM(weight) as total_weight, COUNT(*) as task_count')
#     ->groupBy('project_template_id')
#     ->get()
# Expected: Each template has total_weight = 100
```

**Checklist:**
- [ ] All 5 templates created
- [ ] All 36 tasks created with correct distribution:
  - [ ] Web App: 8 tasks
  - [ ] Mobile App: 7 tasks
  - [ ] E-Commerce: 9 tasks
  - [ ] Integration: 7 tasks
  - [ ] Maintenance: 5 tasks
- [ ] Each template's tasks sum to exactly 100% weight
- [ ] No duplicate task IDs
- [ ] Phase numbers are sequential
- [ ] All descriptions populated

#### Day 2: ProjectTemplateService Testing

Create test file: `tests/Unit/Services/ProjectTemplateServiceTest.php`

```php
<?php
namespace Tests\Unit\Services;

use App\Services\ProjectTemplateService;
use Tests\TestCase;

class ProjectTemplateServiceTest extends TestCase {
    private $service;
    
    protected function setUp(): void {
        parent::setUp();
        $this->service = new ProjectTemplateService();
    }
    
    // Test cases below
}
```

**Test Cases to Implement:**

```php
// 1. Test getAllActiveTemplates()
public function test_get_all_active_templates_returns_collection() {
    $templates = $this->service->getAllActiveTemplates();
    $this->assertIsIterable($templates);
    $this->assertCount(5, $templates);
    $this->assertTrue(collect($templates)->every(fn($t) => $t['is_active']));
}

// 2. Test getTemplateWithTasks()
public function test_get_template_with_tasks_returns_complete_template() {
    $template = $this->service->getTemplateWithTasks(1);
    
    $this->assertIsArray($template);
    $this->assertArrayHasKey('id', $template);
    $this->assertArrayHasKey('name', $template);
    $this->assertArrayHasKey('tasks', $template);
    $this->assertNotEmpty($template['tasks']);
}

// 3. Test validateTemplateWeights() - Valid case
public function test_validate_template_weights_passes_for_valid_template() {
    $isValid = $this->service->validateTemplateWeights(1);
    $this->assertTrue($isValid);
}

// 4. Test validateTemplateWeights() - Invalid case
public function test_validate_template_weights_throws_exception_for_invalid_weight() {
    $this->expectException(\Exception::class);
    // Temporarily modify a template to have weights that don't sum to 100
    // This tests the validation logic
}

// 5. Test createTemplate()
public function test_create_template_creates_new_template() {
    $data = [
        'name' => 'Custom Template',
        'description' => 'Test template',
        'category' => 'Custom',
        'is_active' => true,
        'task_count' => 5,
        'average_duration_days' => 30
    ];
    
    $template = $this->service->createTemplate($data);
    
    $this->assertIsArray($template);
    $this->assertArrayHasKey('id', $template);
    $this->assertEquals('Custom Template', $template['name']);
}

// 6. Test updateTemplate()
public function test_update_template_modifies_existing_template() {
    $result = $this->service->updateTemplate(1, ['is_active' => false]);
    $this->assertTrue($result['success']);
}

// 7. Test deleteTemplate()
public function test_delete_template_removes_template() {
    // Create a template first
    $template = $this->service->createTemplate([...]);
    
    $result = $this->service->deleteTemplate($template['id']);
    $this->assertTrue($result['success']);
}
```

**Checklist:**
- [ ] All 7 test cases written
- [ ] All tests passing: `php artisan test tests/Unit/Services/ProjectTemplateServiceTest.php`
- [ ] Coverage > 95% for ProjectTemplateService
- [ ] No errors in service logic
- [ ] Error handling tested

#### Day 2: OpportunityProjectService Testing

Create test file: `tests/Unit/Services/OpportunityProjectServiceTest.php`

```php
public function test_create_project_with_template_creates_atomically() {
    $opportunityId = 1; // Create test opportunity first
    $templateId = 1;
    $userId = 1;
    
    $result = $this->service->createProjectWithTemplate($opportunityId, $templateId, $userId);
    
    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('project', $result);
    $this->assertArrayHasKey('tasks', $result);
    
    // Verify project created
    $this->assertDatabaseHas('projects', ['id' => $result['project']['id']]);
    
    // Verify all tasks created
    $this->assertEquals(8, count($result['tasks'])); // Web App template has 8 tasks
}

public function test_create_project_with_template_fails_if_opportunity_not_won() {
    $opportunityId = 1; // Create unwon opportunity
    $templateId = 1;
    $userId = 1;
    
    $result = $this->service->createProjectWithTemplate($opportunityId, $templateId, $userId);
    
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('opportunity', strtolower($result['error']));
}

public function test_apply_template_to_project_creates_tasks() {
    $projectId = 1; // Create empty project first
    $templateId = 1;
    $userId = 1;
    
    $result = $this->service->applyTemplateToProject($projectId, $templateId, $userId);
    
    $this->assertTrue($result['success']);
    $this->assertCount(8, $result['tasks']);
}
```

**Checklist:**
- [ ] All 3 test cases written
- [ ] All tests passing
- [ ] Atomic transaction verified (no partial failures)
- [ ] Error cases handled properly
- [ ] Audit logs created for all operations

---

### WEEK 2: API Endpoint Testing (Day 3-4)

#### Day 3: Template Public Endpoints Testing

Create test file: `tests/Feature/TemplateApiTest.php`

```bash
# Manual testing endpoints:

# 1. List templates
curl -X GET http://127.0.0.1:8000/api/templates \
  -H "Authorization: Bearer {token}"
# Expected 200 with all 5 templates

# 2. Get single template
curl -X GET http://127.0.0.1:8000/api/templates/1 \
  -H "Authorization: Bearer {token}"
# Expected 200 with template and all tasks

# 3. Preview template
curl -X GET http://127.0.0.1:8000/api/templates/1/preview \
  -H "Authorization: Bearer {token}"
# Expected 200 with template preview data

# 4. Create project with template
curl -X POST http://127.0.0.1:8000/api/opportunities/1/projects/with-template \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "template_id": 1,
    "project_name": "Web App Project"
  }'
# Expected 201 with created project

# 5. Apply template to project
curl -X POST http://127.0.0.1:8000/api/projects/1/apply-template \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"template_id": 1}'
# Expected 200 with applied tasks
```

**Automated Tests:**

```php
public function test_get_templates_returns_all_active() {
    $response = $this->getJson('/api/templates');
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success', 'data' => ['*' => ['id', 'name', 'tasks']]
    ]);
    $this->assertCount(5, $response->json('data'));
}

public function test_get_template_by_id_returns_with_tasks() {
    $response = $this->getJson('/api/templates/1');
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success', 'data' => ['id', 'name', 'tasks' => ['*' => ['id', 'name', 'weight']]]
    ]);
}

public function test_preview_template_returns_tasks() {
    $response = $this->getJson('/api/templates/1/preview');
    
    $response->assertStatus(200);
    $response->assertJsonStructure(['success', 'data']);
}

public function test_create_project_with_template_validates_input() {
    $response = $this->postJson('/api/opportunities/1/projects/with-template', [
        'template_id' => 999 // Invalid template
    ]);
    
    $response->assertStatus(422);
}

public function test_apply_template_creates_tasks() {
    // Create empty project first
    $project = Project::factory()->create();
    
    $response = $this->postJson('/api/projects/' . $project->id . '/apply-template', [
        'template_id' => 1
    ]);
    
    $response->assertStatus(200);
    $this->assertCount(8, $project->fresh()->tasks);
}
```

**Checklist:**
- [ ] All 5 public endpoints tested
- [ ] All responses return correct HTTP status codes
- [ ] Response structure matches documentation
- [ ] Validation errors handled properly
- [ ] Authentication required for all endpoints
- [ ] Response times < 200ms

#### Day 3: Admin Endpoints Testing

```bash
# Admin endpoints testing:

# 1. List all templates (including inactive)
curl -X GET http://127.0.0.1:8000/api/admin/templates \
  -H "Authorization: Bearer {admin_token}"

# 2. Create new template
curl -X POST http://127.0.0.1:8000/api/admin/templates \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Template",
    "description": "Test",
    "category": "Custom",
    "is_active": true
  }'

# 3. Update template
curl -X PUT http://127.0.0.1:8000/api/admin/templates/1 \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"is_active": false}'

# 4. Delete template
curl -X DELETE http://127.0.0.1:8000/api/admin/templates/6 \
  -H "Authorization: Bearer {admin_token}"

# 5. Add task to template
curl -X POST http://127.0.0.1:8000/api/admin/templates/1/tasks \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Task",
    "description": "Task description",
    "weight": 10,
    "phase_number": 1
  }'

# 6. Update task
curl -X PUT http://127.0.0.1:8000/api/admin/templates/tasks/1 \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{"weight": 15}'

# 7. Delete task
curl -X DELETE http://127.0.0.1:8000/api/admin/templates/tasks/1 \
  -H "Authorization: Bearer {admin_token}"

# 8. Complex: Verify weight validation
# Create template, add tasks with weights summing != 100, verify rejection
```

**Checklist:**
- [ ] All 8 admin endpoints working
- [ ] Admin-only authorization verified
- [ ] Non-admin users get 403 Forbidden
- [ ] Weight validation prevents invalid templates
- [ ] CRUD operations work correctly
- [ ] Cascade delete behavior verified

#### Day 4: Integration & Performance Testing

```bash
# 1. Create project from opportunity with template
# Step 1: Create opportunity
curl -X POST http://127.0.0.1:8000/api/opportunities \
  -H "Authorization: Bearer {token}" \
  -d '{"name": "Test Opportunity", "estimated_value": 10000, "currency": "USD"}'

# Step 2: Mark as won
curl -X PUT http://127.0.0.1:8000/api/opportunities/{id} \
  -H "Authorization: Bearer {token}" \
  -d '{"stage": "won", "probability": 100}'

# Step 3: Create project with template
curl -X POST http://127.0.0.1:8000/api/opportunities/{id}/projects/with-template \
  -H "Authorization: Bearer {token}" \
  -d '{"template_id": 1, "project_name": "Web App"}'

# Step 4: Verify project and tasks created
curl -X GET http://127.0.0.1:8000/api/projects/{project_id}/tasks \
  -H "Authorization: Bearer {token}"
# Expected: 8 tasks for Web App template

# 2. Performance test: Create 50 projects with templates
# Measure: Total time, average per project, database query count

# 3. Load test: Concurrent requests
# Apache Bench: ab -n 100 -c 10 http://127.0.0.1:8000/api/templates

# 4. Database query optimization check
php artisan tinker
# Use Laravel query log to verify no N+1 queries
# > DB::enableQueryLog()
# > $service->getAllActiveTemplates()
# > dd(DB::getQueryLog())
```

**Performance Benchmarks:**

```php
public function test_template_listing_performance() {
    $start = microtime(true);
    $templates = $this->service->getAllActiveTemplates();
    $end = microtime(true);
    
    $duration = ($end - $start) * 1000; // milliseconds
    $this->assertLessThan(100, $duration, "Template listing took {$duration}ms, expected < 100ms");
}

public function test_project_creation_with_template_performance() {
    $start = microtime(true);
    
    $result = $this->service->createProjectWithTemplate(1, 1, 1);
    
    $end = microtime(true);
    $duration = ($end - $start) * 1000;
    
    $this->assertLessThan(500, $duration, "Project creation took {$duration}ms, expected < 500ms");
    $this->assertTrue($result['success']);
}

public function test_no_n_plus_one_queries_on_template_retrieval() {
    DB::enableQueryLog();
    
    $templates = $this->service->getAllActiveTemplates();
    
    $queryCount = count(DB::getQueryLog());
    $this->assertLessThan(5, $queryCount, "Too many queries: {$queryCount} (N+1 issue)");
}
```

**Checklist:**
- [ ] End-to-end flow tested (opportunity → project → tasks)
- [ ] All 13 endpoints responding correctly
- [ ] Performance benchmarks met:
  - [ ] Template listing < 100ms
  - [ ] Template preview < 200ms
  - [ ] Project creation < 500ms
  - [ ] Admin operations < 300ms
- [ ] No N+1 queries
- [ ] Load test with 100 concurrent requests passes
- [ ] Database indexes working efficiently
- [ ] Audit logs created for all operations

---

### WEEK 3: Frontend & User Testing (Day 5-6)

#### Day 5: Template Selection Form Testing

**Manual Testing:**

1. Navigate to `/opportunities/{id}/create-project-with-template`
   - [ ] Page loads in < 2 seconds
   - [ ] Opportunity details display correctly
   - [ ] All 5 template cards visible
   - [ ] No JavaScript console errors

2. Test Template Cards
   - [ ] Template name displays
   - [ ] Description visible
   - [ ] Category badge shows
   - [ ] Task count accurate
   - [ ] Average duration displayed
   - [ ] Hover effects working

3. Test Preview Modal
   - [ ] Click "Preview" button
   - [ ] Modal opens
   - [ ] All tasks list with:
     - [ ] Phase number badge
     - [ ] Task name
     - [ ] Task description
     - [ ] Weight percentage
     - [ ] Estimated duration
   - [ ] Modal closes properly
   - [ ] No layout shift

4. Test Form Submission
   - [ ] Select template
   - [ ] Click "Create Project with This Template"
   - [ ] Form submits via fetch
   - [ ] Success message appears
   - [ ] Redirects to project detail page
   - [ ] All 8 tasks created (verify in task list)

**Automated Frontend Testing:**

```javascript
// Cypress test example: cypress/e2e/template-selection.cy.js

describe('Template Selection Form', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/opportunities/1/create-project-with-template');
  });

  it('displays all template cards', () => {
    cy.get('[data-test="template-card"]').should('have.length', 5);
  });

  it('opens preview modal on button click', () => {
    cy.get('[data-test="template-card"]').first().within(() => {
      cy.get('[data-test="preview-btn"]').click();
    });
    cy.get('[data-test="preview-modal"]').should('be.visible');
  });

  it('displays all tasks in preview modal', () => {
    // Web App template has 8 tasks
    cy.get('[data-test="task-item"]').should('have.length', 8);
  });

  it('creates project on form submission', () => {
    cy.get('[data-test="template-card"]').first().within(() => {
      cy.get('[data-test="create-btn"]').click();
    });
    
    cy.url().should('include', '/projects/');
    cy.get('[data-test="task-list"]').should('exist');
  });
});
```

**Checklist:**
- [ ] All 4 template cards display correctly
- [ ] Preview modal opens/closes
- [ ] All tasks visible with correct data
- [ ] Form submission successful
- [ ] Project created with all tasks
- [ ] Redirects to project detail
- [ ] No console errors
- [ ] Responsive on mobile (test on 375px width)
- [ ] Load time < 2 seconds

#### Day 5: Apply Template Form Testing

1. Navigate to `/projects/{id}/apply-template` (for empty project)
   - [ ] Page loads
   - [ ] Template cards visible
   - [ ] Previous warning message displays (if applicable)

2. Test Application Flow
   - [ ] Select template
   - [ ] Click "Apply Template"
   - [ ] Form submits
   - [ ] Success message shows task count
   - [ ] Redirects to project page
   - [ ] Tasks now visible in project

**Checklist:**
- [ ] Form loads for empty projects
- [ ] Cannot apply to project with tasks
- [ ] Template applied successfully
- [ ] All tasks created
- [ ] Redirect works
- [ ] No page errors

#### Day 6: Admin Dashboard Testing

1. Navigate to `/admin/templates`
   - [ ] Page loads < 1 second
   - [ ] All 5 templates listed in table
   - [ ] Table shows: name, category, task count, duration, status
   - [ ] Proper styling and layout

2. Test Create Template
   - [ ] Click "Create Template"
   - [ ] Modal opens
   - [ ] Form validates (test with empty fields)
   - [ ] Submit creates template
   - [ ] New template appears in list
   - [ ] Modal closes

3. Test Edit Template
   - [ ] Click edit button on template
   - [ ] Modal opens with current data
   - [ ] Modify fields
   - [ ] Submit updates template
   - [ ] Changes reflected immediately

4. Test Delete Template
   - [ ] Click delete button
   - [ ] Confirmation dialog appears
   - [ ] Confirm delete
   - [ ] Template removed from list

5. Test Task Management
   - [ ] Click "Manage Tasks" on template
   - [ ] Tasks modal opens
   - [ ] All tasks list with details
   - [ ] Add task button works
   - [ ] New task appears
   - [ ] Edit task works
   - [ ] Delete task works

6. Test Validation
   - [ ] Cannot create template without name
   - [ ] Cannot create duplicate names
   - [ ] Cannot add task without name
   - [ ] Weight validation (must be 0-100)
   - [ ] Error messages clear

**Automated Admin Testing:**

```javascript
// Cypress test: admin-templates.cy.js

describe('Admin Template Dashboard', () => {
  beforeEach(() => {
    cy.loginAsAdmin();
    cy.visit('/admin/templates');
  });

  it('displays all templates', () => {
    cy.get('[data-test="template-row"]').should('have.length', 5);
  });

  it('creates new template', () => {
    cy.get('[data-test="create-btn"]').click();
    cy.fillForm({
      name: 'Custom Template',
      category: 'Custom',
      is_active: true
    });
    cy.get('[data-test="save-btn"]').click();
    cy.get('[data-test="success-message"]').should('be.visible');
  });

  it('manages template tasks', () => {
    cy.get('[data-test="template-row"]').first().within(() => {
      cy.get('[data-test="tasks-btn"]').click();
    });
    
    cy.get('[data-test="task-list"]').should('be.visible');
    cy.get('[data-test="task-item"]').should('have.length.greaterThan', 0);
  });
});
```

**Checklist:**
- [ ] Dashboard loads quickly (< 1 second)
- [ ] All templates display
- [ ] Create template works
- [ ] Edit template works
- [ ] Delete template works (with confirmation)
- [ ] Task management modals work
- [ ] Add/edit/delete tasks works
- [ ] Validation prevents errors
- [ ] Error messages clear and helpful
- [ ] No console errors
- [ ] Responsive design
- [ ] AJAX operations complete successfully

---

### WEEK 4: Quality Assurance (Day 7-10)

#### Day 7: Cross-Browser Testing

Test on:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (if available)
- [ ] Edge (latest)
- [ ] Chrome Mobile (375px viewport)
- [ ] Safari Mobile (375px viewport)

**Browser Compatibility Checklist:**
- [ ] Modals display correctly
- [ ] Forms submit successfully
- [ ] AJAX requests work
- [ ] No layout shift
- [ ] Responsive design works
- [ ] No console errors
- [ ] Font rendering correct
- [ ] Colors display properly

#### Day 8: Security Testing

```bash
# 1. SQL Injection Testing
# Try malicious input: " OR 1=1 --
# Expected: Sanitized/quoted, no sql injection

# 2. XSS Testing
# Template name: <script>alert('xss')</script>
# Expected: Escaped, displayed as text

# 3. CSRF Protection
# Send form without CSRF token
# Expected: 419 Token Mismatch

# 4. Authorization Testing
# PM tries to delete template
# Expected: 403 Forbidden

# 5. Rate Limiting
# Send 100+ requests to /api/templates in 1 minute
# Expected: 429 Too Many Requests after limit
```

**Checklist:**
- [ ] SQL injection attempts blocked
- [ ] XSS attempts escaped
- [ ] CSRF protection working
- [ ] Authorization enforced (admin-only for admin endpoints)
- [ ] Rate limiting active
- [ ] No sensitive data in logs

#### Day 9: Audit Trail Verification

```php
public function test_audit_log_created_on_project_creation_with_template() {
    $result = $this->service->createProjectWithTemplate(1, 1, 1);
    
    $auditEntries = DB::table('audit_logs')
        ->where('entity_id', $result['project']['id'])
        ->where('action', 'create')
        ->get();
    
    $this->assertGreaterThan(0, $auditEntries->count());
    $this->assertStringContainsString('template_based_creation', $auditEntries->first()->source);
}

public function test_audit_log_includes_all_created_tasks() {
    $result = $this->service->createProjectWithTemplate(1, 1, 1);
    
    $taskAuditCount = DB::table('audit_logs')
        ->where('entity_type', 'tasks')
        ->where('action', 'create')
        ->count();
    
    // 8 tasks created = 8 audit entries
    $this->assertEquals(8, $taskAuditCount);
}
```

**Checklist:**
- [ ] Audit entries created for project creation
- [ ] Audit entries created for each task creation
- [ ] Audit entries include source (template_based_creation)
- [ ] User ID logged correctly
- [ ] Timestamp accurate
- [ ] Audit log searchable by entity_id

#### Day 10: Final Launch Preparation

**Pre-Launch Verification:**

```bash
# 1. Run full test suite
php artisan test

# 2. Code quality check
php artisan analyze  # if using PHPStan

# 3. Database integrity check
# Verify all tables have correct structure
php artisan migrate:status

# 4. Performance profiling
# Monitor query count and execution time
php artisan tinker
# > DB::enableQueryLog()
# > // Run operations
# > dd(DB::getQueryLog())

# 5. API documentation
# Verify all endpoints documented in api_contracts.md

# 6. Deployment readiness
# - All code committed
# - All tests passing
# - Documentation complete
# - Rollback plan documented
```

**Final Checklist:**
- [ ] All unit tests passing (> 95% coverage)
- [ ] All integration tests passing
- [ ] All E2E tests passing
- [ ] No critical bugs
- [ ] No security vulnerabilities
- [ ] Performance meets benchmarks
- [ ] Code quality acceptable
- [ ] Documentation complete
- [ ] Team trained
- [ ] Stakeholders approved

---

## 🚀 LAUNCH EXECUTION

### Pre-Launch (1 hour before)

```bash
# 1. Backup production database
./scripts/backup-database.sh

# 2. Announce maintenance window (if needed)
# Update status page

# 3. Verify production database connection
php artisan tinker
# > config('database.default')
# > DB::connection()->getPdo()

# 4. Have rollback commands ready
# - Git revert command
# - Database rollback SQL
```

### Launch (Execute in this order)

```bash
# 1. Deploy code
git pull origin master

# 2. Install any new dependencies
composer install --no-dev

# 3. Run migrations
php artisan migrate --force

# 4. Seed templates
php artisan db:seed --class=ProjectTemplateSeeder

# 5. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 6. Health check
curl http://localhost/api/health

# 7. Test API endpoints
curl http://localhost/api/templates

# 8. Monitor logs
tail -f storage/logs/laravel.log
```

### Post-Launch (1 hour after)

```bash
# 1. Monitor error logs
# Check for exceptions, 5xx errors

# 2. Verify audit logs
# Check that template operations are being logged

# 3. Test critical user flows
# - Template selection
# - Project creation with template
# - Admin template management

# 4. Check performance
# - API response times
# - Database query log
# - Cache hit rates

# 5. Gather feedback
# - User reports
# - Support tickets
# - System metrics
```

---

## 📊 SUCCESS METRICS (Post-Launch)

**Track for First Week:**

| Metric | Target | Actual |
|--------|--------|--------|
| API response time (average) | < 200ms | ___ |
| Error rate | < 0.1% | ___ |
| Template usage rate | > 50% | ___ |
| Projects created with templates | _/ week | ___ |
| Admin dashboard usage | _/ day | ___ |
| User satisfaction | > 4/5 | ___ |
| Support tickets | < 5 | ___ |
| Data integrity issues | 0 | ___ |

---

## 🎯 SIGN-OFF

- [ ] Technical Lead: ___________  Date: ______
- [ ] QA Manager: ___________  Date: ______
- [ ] Product Owner: ___________  Date: ______
- [ ] Operations: ___________  Date: ______

---

**Phase 5.4 Status:** Ready to Execute Testing on Day 1  
**Estimated Completion:** February 28-29, 2026  
**Confidence Level:** High (90%)
