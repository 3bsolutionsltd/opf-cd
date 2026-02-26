# Project Templates & Workplan Generation (Planned Feature)

**Status:** Not Implemented  
**Priority:** High (Professional Services Essential)  
**Proposed Phase:** Phase 4 (Post-Production Enhancement)

---

## Business Problem

When an opportunity is won, project managers currently must:
1. Manually create all project tasks
2. Assign appropriate weights to each task
3. Define phases and breakdown structure
4. Set realistic timelines for each deliverable

**Pain Points:**
- Time-consuming manual setup (30-60 minutes per project)
- Inconsistent project structures across similar project types
- Risk of missed critical phases (testing, deployment, etc.)
- New project managers lack guidance on professional breakdown

**Example Scenario:**
- Sales team wins "Mobile Banking App" bid
- System creates empty project shell
- PM must manually define: Requirements, Design, iOS Dev, Android Dev, Backend, Testing, Deployment
- Every mobile app project requires same manual setup

---

## Solution: Intelligent Project Templates

When an opportunity is won and converted to a project, the system should:

1. **Detect project type** (from opportunity data or user selection)
2. **Apply appropriate template** with pre-defined task structure
3. **Generate workplan** with phases, tasks, weights, and durations
4. **Provide implementation guidance** with best practices

---

## Project Types & Templates

### Template 1: Web Application Development

**Use Cases:** Corporate websites, web portals, SaaS platforms

**Task Breakdown:**
| Phase | Task Name | Weight | Typical Duration | Status |
|-------|-----------|--------|------------------|--------|
| Planning | Requirements Gathering & Analysis | 5% | 1-2 weeks | todo |
| Design | UI/UX Design & Prototyping | 10% | 2-3 weeks | todo |
| Design | Design Approval & Revisions | 5% | 1 week | todo |
| Development | Frontend Development | 25% | 4-6 weeks | todo |
| Development | Backend Development & APIs | 30% | 5-7 weeks | todo |
| Development | Database Design & Implementation | 10% | 2-3 weeks | todo |
| Quality | Testing & Quality Assurance | 10% | 2-3 weeks | todo |
| Deployment | Deployment & Go-Live | 5% | 1 week | todo |

**Total Weight:** 100%  
**Estimated Timeline:** 18-28 weeks

---

### Template 2: Mobile Application (iOS + Android)

**Use Cases:** Mobile apps, cross-platform applications

**Task Breakdown:**
| Phase | Task Name | Weight | Typical Duration | Status |
|-------|-----------|--------|------------------|--------|
| Planning | Requirements & Feature Definition | 5% | 1-2 weeks | todo |
| Design | UI/UX Design for Mobile | 15% | 3-4 weeks | todo |
| Development | iOS Development | 25% | 6-8 weeks | todo |
| Development | Android Development | 25% | 6-8 weeks | todo |
| Development | Backend API Development | 15% | 4-5 weeks | todo |
| Quality | Testing (iOS, Android, API) | 10% | 3-4 weeks | todo |
| Deployment | App Store Deployment | 5% | 1-2 weeks | todo |

**Total Weight:** 100%  
**Estimated Timeline:** 24-33 weeks

---

### Template 3: E-Commerce Platform

**Use Cases:** Online stores, marketplace platforms

**Task Breakdown:**
| Phase | Task Name | Weight | Typical Duration | Status |
|-------|-----------|--------|------------------|--------|
| Planning | Business Requirements & Analysis | 5% | 1-2 weeks | todo |
| Design | User Experience Design | 10% | 2-3 weeks | todo |
| Development | Product Catalog System | 15% | 3-4 weeks | todo |
| Development | Shopping Cart & Checkout | 15% | 3-4 weeks | todo |
| Development | Payment Gateway Integration | 10% | 2-3 weeks | todo |
| Development | Order Management System | 10% | 2-3 weeks | todo |
| Development | Admin Dashboard | 10% | 2-3 weeks | todo |
| Quality | Testing & Security Audit | 15% | 3-4 weeks | todo |
| Deployment | Go-Live & Monitoring Setup | 10% | 2 weeks | todo |

**Total Weight:** 100%  
**Estimated Timeline:** 20-28 weeks

---

### Template 4: Custom Software Integration

**Use Cases:** API integrations, system migrations, enterprise software

**Task Breakdown:**
| Phase | Task Name | Weight | Typical Duration | Status |
|-------|-----------|--------|------------------|--------|
| Discovery | System Analysis & Integration Planning | 10% | 2-3 weeks | todo |
| Design | Integration Architecture Design | 10% | 2 weeks | todo |
| Development | API Development & Mapping | 30% | 5-6 weeks | todo |
| Development | Data Migration Scripts | 15% | 3-4 weeks | todo |
| Development | Error Handling & Logging | 10% | 2 weeks | todo |
| Testing | Integration Testing | 15% | 3-4 weeks | todo |
| Deployment | Rollout & Monitoring | 10% | 2 weeks | todo |

**Total Weight:** 100%  
**Estimated Timeline:** 19-24 weeks

---

### Template 5: Maintenance & Support Project

**Use Cases:** Ongoing maintenance contracts, retainer agreements

**Task Breakdown:**
| Phase | Task Name | Weight | Typical Duration | Status |
|-------|-----------|--------|------------------|--------|
| Operations | Bug Fixes & Issue Resolution | 40% | Ongoing | in_progress |
| Operations | Performance Monitoring | 15% | Ongoing | in_progress |
| Enhancement | Feature Enhancements | 25% | Ongoing | todo |
| Support | Technical Support & Documentation | 10% | Ongoing | in_progress |
| Management | Project Management & Reporting | 10% | Ongoing | in_progress |

**Total Weight:** 100%  
**Timeline:** Ongoing (typically 6-12 month contracts)

---

## Implementation Design

### Database Schema Changes

#### New Table: `project_templates`

```sql
CREATE TABLE project_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL, -- 'web_app', 'mobile_app', 'ecommerce', 'integration', 'maintenance'
    description TEXT,
    estimated_duration_weeks INT, -- typical project duration
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### New Table: `project_template_tasks`

```sql
CREATE TABLE project_template_tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    phase_name VARCHAR(100) NOT NULL, -- 'Planning', 'Design', 'Development', etc.
    task_name VARCHAR(255) NOT NULL,
    weight DECIMAL(5,2) NOT NULL, -- percentage weight
    sort_order INT NOT NULL, -- for display order
    typical_duration_weeks INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES project_templates(id) ON DELETE CASCADE
);
```

#### Modify `opportunities` Table

```sql
ALTER TABLE opportunities 
ADD COLUMN project_type VARCHAR(100) AFTER currency;
-- Values: 'web_app', 'mobile_app', 'ecommerce', 'integration', 'maintenance', 'custom'
```

---

### Service Architecture

#### New Service: `ProjectTemplateService`

**Responsibility:** Manage project templates and task definitions

```php
class ProjectTemplateService
{
    /**
     * Get all active project templates.
     * 
     * @return array List of templates
     */
    public function getAllTemplates(): array;

    /**
     * Get template by ID with all tasks.
     * 
     * @param int $templateId
     * @return array|null Template data with tasks
     */
    public function getTemplateWithTasks(int $templateId): ?array;

    /**
     * Get template by category.
     * 
     * @param string $category
     * @return array|null Template data with tasks
     */
    public function getTemplateByCategory(string $category): ?array;
}
```

#### Enhanced Service: `OpportunityProjectService`

Add template application capability:

```php
class OpportunityProjectService
{
    private ProjectTemplateService $templateService;
    private TaskManagementService $taskManagementService;

    /**
     * Create project from won opportunity with template.
     * 
     * If opportunity has project_type, applies corresponding template.
     * Creates project + pre-populated tasks in single transaction.
     * 
     * @param int $opportunityId
     * @param int $userId
     * @param int|null $templateId Optional: override auto-detection
     * @param Request|null $request
     * @return array ['success' => bool, 'project_id' => int|null, 'tasks_created' => int, 'message' => string]
     */
    public function createProjectWithTemplate(
        int $opportunityId, 
        int $userId, 
        ?int $templateId = null,
        ?Request $request = null
    ): array;
}
```

---

### User Workflows

#### Workflow 1: Automatic Template Application

1. **Opportunity Creation:** Sales team creates opportunity and selects "Project Type" (Web App, Mobile App, etc.)
2. **Opportunity Won:** When stage changes to "won", system:
   - Creates project (existing behavior)
   - Detects project_type from opportunity
   - Finds matching template
   - Auto-creates all template tasks with pre-defined weights
3. **Project Manager Review:** PM reviews generated workplan and adjusts as needed

**API Flow:**
```
Opportunity.stage = 'won' → Triggers OpportunityProjectService
  → Creates project
  → ProjectTemplateService.getTemplateByCategory('web_app')
  → TaskManagementService.createTask() for each template task
  → Returns project_id + tasks_created count
```

---

#### Workflow 2: Manual Template Selection

1. **Opportunity Won:** User manually creates project from opportunity
2. **Template Selection:** UI shows "Apply Template?" dropdown with available templates
3. **Template Application:** User selects template (e.g., "Mobile App Development")
4. **Customization:** User can modify task list before final creation

**API Endpoint:**
```
POST /api/opportunities/{opportunityId}/projects/with-template

Body:
{
  "name": "Banking App - Phase 1",
  "contract_value": 50000,
  "contract_currency": "USD",
  "start_date": "2026-03-01",
  "end_date": "2026-12-31",
  "status": "planned",
  "project_lead_id": 5,
  "template_id": 2  // Mobile App template
}

Response:
{
  "success": true,
  "project_id": 123,
  "tasks_created": 7,
  "message": "Project created with 7 tasks from Mobile App template"
}
```

---

#### Workflow 3: Template Management (Admin)

**Features:**
- View all templates
- Create custom templates
- Edit existing templates
- Activate/deactivate templates
- Clone templates for customization

**UI Pages:**
- `/admin/templates` - List all templates
- `/admin/templates/create` - Create new template
- `/admin/templates/{id}/edit` - Edit template and tasks

---

### API Endpoints

#### Template Management (Admin)

```
GET    /api/admin/templates                      - List all templates
GET    /api/admin/templates/{id}                 - Get template details
POST   /api/admin/templates                       - Create template
PUT    /api/admin/templates/{id}                 - Update template
DELETE /api/admin/templates/{id}                  - Delete template

GET    /api/admin/templates/{id}/tasks           - Get template tasks
POST   /api/admin/templates/{id}/tasks           - Add task to template
PUT    /api/admin/templates/tasks/{taskId}       - Update template task
DELETE /api/admin/templates/tasks/{taskId}       - Delete template task
```

#### Template Usage (Project Managers)

```
GET  /api/templates                               - List active templates (for selection)
GET  /api/templates/{id}                          - Get template preview
POST /api/opportunities/{id}/projects/with-template - Create project with template
```

---

## Benefits

### 1. **Time Savings**
- **Before:** 30-60 minutes manual task setup per project
- **After:** 2-3 minutes review and minor adjustments
- **ROI:** 90% reduction in project setup time

### 2. **Consistency**
- All web app projects follow same professional structure
- Standardized task weights ensure realistic progress tracking
- Reduces variance between junior and senior PMs

### 3. **Best Practices Built-In**
- Templates encode industry-standard project phases
- Ensures critical phases (testing, security, deployment) are never forgotten
- New PMs get professional guidance automatically

### 4. **Faster Project Starts**
- Projects move to "active" status faster
- Teams know immediately what needs to be done
- Clearer expectations for delivery timeline

### 5. **Better Estimates**
- Template durations provide baseline for contract negotiations
- Historical data can refine template accuracy over time
- Opportunity stage estimates align with actual project timelines

---

## Implementation Roadmap

### Phase 1: Database & Seeding (Week 1)

- [ ] Create `project_templates` table
- [ ] Create `project_template_tasks` table
- [ ] Add `project_type` column to `opportunities` table
- [ ] Seed 5 default templates (Web App, Mobile App, E-Commerce, Integration, Maintenance)
- [ ] Write migration scripts with rollback support

### Phase 2: Core Services (Week 1-2)

- [ ] Create `ProjectTemplateService`
  - getAllTemplates()
  - getTemplateWithTasks()
  - getTemplateByCategory()
- [ ] Enhance `OpportunityProjectService`
  - Add createProjectWithTemplate() method
  - Auto-detect project type from opportunity
  - Create tasks in transaction with project
- [ ] Write unit tests for template service
- [ ] Write integration tests for project+tasks creation

### Phase 3: API Endpoints (Week 2)

- [ ] `GET /api/templates` - List templates for selection
- [ ] `GET /api/templates/{id}` - Template preview
- [ ] `POST /api/opportunities/{id}/projects/with-template` - Create with template
- [ ] Add template_id parameter to existing manual project creation endpoint
- [ ] Update API documentation

### Phase 4: Frontend Integration (Week 2-3)

- [ ] Add "Project Type" dropdown to opportunity create/edit forms
- [ ] Create template selection UI in project creation flow
- [ ] Show template preview (tasks list with weights)
- [ ] Add "Apply Template" button to existing projects (optional)
- [ ] Update opportunity details page to show selected project type

### Phase 5: Admin Interface (Week 3)

- [ ] Admin templates list page (`/admin/templates`)
- [ ] Template create/edit forms
- [ ] Template tasks management (add/edit/delete/reorder)
- [ ] Template activation toggle
- [ ] Template clone functionality

### Phase 6: Testing & Refinement (Week 3-4)

- [ ] End-to-end testing with all 5 templates
- [ ] Validate task weight sums = 100% for all templates
- [ ] Performance testing (project+tasks creation speed)
- [ ] User acceptance testing with project managers
- [ ] Documentation updates

---

## Future Enhancements (Post-Launch)

### Dynamic Templates with AI (Phase 5)

- Analyze completed projects to refine template accuracy
- AI-suggested task durations based on historical data
- Custom templates per client (e.g., "Banking App Template for Client X")

### Payment Milestone Templates

- Pre-define payment milestones aligned with project phases
- Example: 30% upfront, 40% at development completion, 30% at go-live

### Resource Planning Templates

- Suggest team composition (e.g., 2 developers, 1 designer, 1 QA)
- Estimate person-hours per task

### Template Marketplace

- Share templates across organization
- Export/import templates for different business units

---

## Technical Considerations

### Data Integrity

- **Task Weight Validation:** Ensure sum = 100% for each template
- **Transaction Safety:** Project + tasks creation must be atomic (rollback on failure)
- **Template Versioning:** Track changes to templates over time

### Performance

- **Batch Insert:** Use bulk insert for multiple tasks
- **Caching:** Cache active templates to reduce database queries
- **Indexing:** Index `project_templates.category` for fast lookups

### Backward Compatibility

- **Existing Projects:** Not affected (templates only apply to new projects)
- **Optional Feature:** Projects can still be created without templates
- **No Migration Required:** Existing projects don't need template assignment

---

## Success Metrics

### Quantitative

- Average project setup time (target: <5 minutes)
- Percentage of projects using templates (target: >80%)
- Task weight accuracy (target: 98% of projects sum to 100%)

### Qualitative

- Project manager satisfaction survey
- Reduction in "forgotten tasks" incidents
- Consistency of project structures

---

## Stakeholder Benefits

### Sales Team

- Can discuss realistic timelines during negotiations
- Project type selection helps qualify opportunities
- Clearer handoff to delivery team

### Project Managers

- Massive time savings on project setup
- Professional workplan structure out-of-the-box
- Focus on customization rather than starting from scratch

### Delivery Team

- Clear task breakdown from day one
- Consistent project structure across all projects
- Know what to expect regardless of PM

### Finance Team

- Better payment milestone alignment with project phases
- More accurate progress tracking
- Improved earned value calculations

---

## References

- **Current Implementation:** `OpportunityProjectService.php`
- **PMBOK Guide:** WBS (Work Breakdown Structure) best practices
- **Industry Standards:** Standard SDLC phases
- **Related Features:** Task management, project progress tracking

---

## Decision Required

**Should this feature be implemented?**

**Recommendation:** YES - High value, relatively low complexity

**Estimated Effort:** 3-4 weeks for full implementation  
**Suggested Timeline:** Implement after current uncommitted changes are deployed  
**Priority Justification:** Professional services companies need this for operational efficiency

---

**Next Steps:**
1. Review this plan with stakeholders
2. Prioritize which templates to implement first (start with 2-3)
3. Decide on automatic vs. manual template application preference
4. Approve database schema changes
5. Schedule development sprint

