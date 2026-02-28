# Strategic Vision: Actionable Task Breakdown

**Generated:** February 27, 2026  
**Source Document:** STRATEGIC_VISION_INTELLIGENT_OPERATIONS.md  
**Purpose:** Detailed implementation tasks for NOT YET IMPLEMENTED sections  
**Architectural Governance:** ALL implementations MUST follow docs/copilot_rules.md

---

## Executive Summary

### Completion Status

| Section | Status | Completion % | Notes |
|---------|--------|--------------|-------|
| 1. Business Health KPIs & KPAs | ❌ NOT IMPLEMENTED | 0% | Analytics foundation |
| 2. Lead Qualification & Scoring | ❌ NOT IMPLEMENTED | 0% | Sales methodology |
| 3. Cost Determination for Negotiation | ❌ NOT IMPLEMENTED | 0% | Pricing intelligence |
| **4. Project Templates & Workplan** | **✅ COMPLETE** | **100%** | **Phase 5.4 delivered Feb 27** |
| 5. Project Costing & Profitability | 🟡 PARTIALLY PLANNED | 10% | Reference doc exists, not implemented |
| 6. 24/7 Operational Command Center | ❌ NOT IMPLEMENTED | 0% | Decision support platform |
| 7. Smart Business Assistant (AI Agent) | ❌ NOT IMPLEMENTED | 0% | Conversational AI |
| 8. Marketing Copilot | ❌ NOT IMPLEMENTED | 0% | Lead generation system |
| 9. Integration Model | 🟡 CONCEPTUAL | 0% | Cross-module intelligence |

### Summary Statistics

- **Sections Complete:** 1 of 9 (11%)
- **Sections Not Implemented:** 7 of 9 (78%)
- **Sections Partially Planned:** 1 of 9 (11%)
- **Total Estimated Effort:** 520-640 hours (13-16 weeks with 1 developer)
- **Recommended Team Size:** 2-3 developers for parallel execution

### Phases Overview

| Phase | Focus Area | Duration | Priority | Business Value |
|-------|------------|----------|----------|----------------|
| **Phase 5.1** | Notifications & Alerts | 2-3 weeks | HIGH | Proactive monitoring (planned in PRODUCTION_ROADMAP.md) |
| **Phase 5.2** | Business Health KPIs | 3-4 weeks | HIGH | Strategic visibility |
| **Phase 5.3** | Lead Qualification & Cost Determination | 3-4 weeks | HIGH | Sales effectiveness |
| **Phase 5.4** | ~~Project Templates~~ | ~~3-4 weeks~~ | ~~HIGH~~ | ✅ **COMPLETE** |
| **Phase 6.1** | Project Costing & EVM | 4-5 weeks | MEDIUM | Profitability tracking |
| **Phase 6.2** | Operational Command Center | 6-8 weeks | MEDIUM | Decision support |
| **Phase 7.1** | AI Agent Infrastructure | 8-10 weeks | LOW | Strategic assistant |
| **Phase 7.2** | Marketing Copilot | 6-8 weeks | LOW | Lead generation |

---

## Section 1: Business Health KPIs & KPAs

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** HIGH  
**Business Value:** Executive visibility into organizational health  
**Dependencies:** None (works with existing data)  
**Estimated Effort:** 80-100 hours (2-2.5 weeks)

### Problem Statement

Currently, opportunities, projects, accounts, and transactions exist as isolated entities with no unified view of organizational health or performance trends.

### Tasks Breakdown

#### TASK 1.1: Database Schema - Business Metrics
**ID:** BH-001  
**Priority:** High  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Create `business_metrics` table to store calculated KPIs with historical tracking.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_business_metrics_table.php`

**Implementation Details:**
```sql
CREATE TABLE business_metrics (
    id SERIAL PRIMARY KEY,
    metric_type VARCHAR(100) NOT NULL,
    period VARCHAR(50) NOT NULL,
    metric_value NUMERIC(15,2) NOT NULL,
    target_value NUMERIC(15,2) NULL,
    status VARCHAR(20) NOT NULL CHECK (status IN ('on_track', 'at_risk', 'behind')),
    entity_filter JSON NULL,
    calculated_at TIMESTAMP WITH TIME ZONE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE INDEX idx_business_metrics_type_period ON business_metrics(metric_type, period);
CREATE INDEX idx_business_metrics_calculated ON business_metrics(calculated_at);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] All columns have COMMENT statements
- [ ] Indexes created
- [ ] Follows data_modeling_guide.md patterns

**Architecture Notes:**
- Follow migration header pattern from data_modeling_guide.md
- Use `NUMERIC(15,2)` for metric values
- Use `TIMESTAMP WITH TIME ZONE` for all timestamps
- Add comprehensive COMMENT statements

---

#### TASK 1.2: Database Schema - Business Goals
**ID:** BH-002  
**Priority:** High  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Create `business_goals` table to store business targets and track progress.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_business_goals_table.php`

**Implementation Details:**
```sql
CREATE TABLE business_goals (
    id SERIAL PRIMARY KEY,
    goal_type VARCHAR(100) NOT NULL,
    period VARCHAR(50) NOT NULL,
    target_value NUMERIC(15,2) NOT NULL,
    current_value NUMERIC(15,2) DEFAULT 0 NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'on_track', 'at_risk', 'behind', 'achieved')),
    progress_percentage NUMERIC(5,2) DEFAULT 0 NOT NULL CHECK (progress_percentage >= 0 AND progress_percentage <= 100),
    prescriptive_actions JSON NULL,
    created_by INT REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] Foreign key constraints work
- [ ] Check constraints validated
- [ ] Database compliance verified

---

#### TASK 1.3: Service - BusinessMetricsService
**ID:** BH-003  
**Priority:** High  
**Effort:** 24 hours  
**Dependencies:** BH-001, BH-002

**Description:**  
Create service to calculate opportunity KPIs (conversion rate, sales velocity, pipeline value, etc.).

**Required Files:**
- Service: `app/Services/BusinessMetricsService.php`

**Methods to Implement:**
```php
public function calculateOpportunityConversionRate(string $period): array
public function calculateSalesVelocity(string $period): array
public function getPipelineValueByStage(): array
public function calculateOpportunityToProjectConversion(string $period): array
public function calculateAverageDealSize(string $period): array
public function calculateStageConversionRates(string $period): array
```

**Success Criteria:**
- [ ] Service follows single responsibility principle
- [ ] Returns facts only, never decisions
- [ ] All calculations use raw database queries
- [ ] No business logic in constructors
- [ ] Comprehensive PHPDoc comments

**Architecture Notes:**
- Each method does ONE calculation only
- Return structured arrays with clear keys
- Use DB facade for queries (no Eloquent for metrics)
- Calculate from audit_logs for stage transitions
- Period format: 'Q1_2026', '2026-02', 'weekly_2026-W08'

---

#### TASK 1.4: Service - GoalTrackingService
**ID:** BH-004  
**Priority:** High  
**Effort:** 16 hours  
**Dependencies:** BH-002, BH-003

**Description:**  
Create service to update goal progress and generate prescriptive actions.

**Required Files:**
- Service: `app/Services/GoalTrackingService.php`

**Methods to Implement:**
```php
public function updateGoalProgress(): array
public function calculateGoalProgress(int $goalId): array
public function generatePrescriptiveActions(int $goalId): array
public function getActiveGoals(): array
```

**Success Criteria:**
- [ ] Service injects NO other business services (calculations only)
- [ ] Prescriptive actions based on simple rules
- [ ] Returns structured action recommendations
- [ ] Updates goal progress_percentage correctly

**Architecture Notes:**
- Keep prescriptive action logic simple (no AI yet)
- Example: "Gap: $50K, Action: Close Opportunity #12 ($45K)"
- Calculate progress from business_metrics or direct queries
- Store prescriptive_actions as JSON array

---

#### TASK 1.5: Controller - BusinessMetricsController
**ID:** BH-005  
**Priority:** High  
**Effort:** 8 hours  
**Dependencies:** BH-003

**Description:**  
Create thin controller for business metrics API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/BusinessMetricsController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
GET /api/metrics/opportunity-conversion-rate?period={period}
GET /api/metrics/sales-velocity?period={period}
GET /api/metrics/pipeline-value
GET /api/metrics/opportunity-to-project-conversion?period={period}
GET /api/metrics/average-deal-size?period={period}
GET /api/metrics/stage-conversion-rates?period={period}
```

**Success Criteria:**
- [ ] Controller injects ONLY BusinessMetricsService
- [ ] No calculations in controller methods
- [ ] No data transformation in controller
- [ ] Returns service responses directly as JSON
- [ ] Uses $request->get('authenticated_user_id') for auth
- [ ] Proper middleware applied (auth, permissions)

**Architecture Notes:**
- Follow VALID Controller pattern from copilot_rules.md
- Controller is pure pass-through
- Example:
```php
public function getOpportunityConversionRate(Request $request): JsonResponse
{
    $period = $request->input('period', 'Q1_2026');
    $result = $this->metricsService->calculateOpportunityConversionRate($period);
    return response()->json($result);
}
```

---

#### TASK 1.6: Controller - GoalTrackingController
**ID:** BH-006  
**Priority:** High  
**Effort:** 8 hours  
**Dependencies:** BH-004

**Description:**  
Create thin controller for goal tracking API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/GoalTrackingController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
GET /api/goals
POST /api/goals
GET /api/goals/{id}
PUT /api/goals/{id}/update-progress
GET /api/goals/{id}/prescriptive-actions
```

**Success Criteria:**
- [ ] Injects ONLY GoalTrackingService
- [ ] Thin pass-through pattern
- [ ] No calculations or transformations
- [ ] Proper auth and permissions

---

#### TASK 1.7: Scheduled Job - MetricsCalculationJob
**ID:** BH-007  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** BH-003

**Description:**  
Create scheduled job to calculate and store metrics daily.

**Required Files:**
- Job: `app/Console/Commands/CalculateBusinessMetrics.php`
- Schedule: Update `app/Console/Kernel.php`

**Implementation:**
```php
// Calculates metrics at 1 AM daily
public function handle()
{
    $metrics = [
        'opportunity_conversion_rate' => $this->metricsService->calculateOpportunityConversionRate('current_quarter'),
        'sales_velocity' => $this->metricsService->calculateSalesVelocity('current_quarter'),
        // ... other metrics
    ];
    
    // Store in business_metrics table
    foreach ($metrics as $type => $data) {
        DB::table('business_metrics')->insert([
            'metric_type' => $type,
            'period' => 'current_quarter',
            'metric_value' => $data['value'],
            'calculated_at' => now(),
            'created_at' => now()
        ]);
    }
}
```

**Success Criteria:**
- [ ] Runs daily at 1 AM via Laravel scheduler
- [ ] Calculates all KPIs
- [ ] Stores results in business_metrics table
- [ ] Logs completion/errors

---

#### TASK 1.8: Frontend - Metrics Dashboard
**ID:** BH-008  
**Priority:** High  
**Effort:** 20 hours  
**Dependencies:** BH-005, BH-006

**Description:**  
Create dashboard view to display business health KPIs.

**Required Files:**
- Blade View: `resources/views/metrics/dashboard.blade.php`
- Alpine.js Component: Embedded in Blade view

**UI Elements:**
- Overall business health score (0-100)
- Opportunity conversion rate chart
- Sales velocity trend
- Pipeline value by stage (bar chart)
- Average deal size
- Top 5 KPIs with status indicators

**Success Criteria:**
- [ ] No calculations in frontend (display only)
- [ ] Alpine.js for data fetching only
- [ ] Charts use data exactly as returned from API
- [ ] Responsive design with Tailwind CSS
- [ ] Loading states and error handling

**Architecture Notes:**
- Alpine.js pattern:
```javascript
x-data="{
    metrics: {},
    loading: true,
    async fetchMetrics() {
        const response = await fetch('/api/metrics/...');
        this.metrics = await response.json();
        this.loading = false;
    }
}"
x-init="fetchMetrics()"
```

---

#### TASK 1.9: Testing - BusinessMetrics Integration Tests
**ID:** BH-009  
**Priority:** High  
**Effort:** 16 hours  
**Dependencies:** BH-003, BH-005

**Description:**  
Create integration tests for business metrics calculation and API endpoints.

**Required Files:**
- Test: `tests/Feature/BusinessMetricsTest.php`

**Test Cases:**
```php
test_calculate_opportunity_conversion_rate()
test_calculate_sales_velocity()
test_get_pipeline_value_by_stage()
test_opportunity_to_project_conversion()
test_average_deal_size()
test_stage_conversion_rates()
test_metrics_api_endpoints_require_auth()
test_metrics_dashboard_loads()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Code coverage >80% for services
- [ ] Tests use database transactions (rollback)
- [ ] Tests follow Phase 5.4.5 patterns

---

### Section 1 Summary

**Total Tasks:** 9  
**Total Effort:** 108 hours (2.7 weeks)  
**Priority:** HIGH  
**Business Impact:** Executive visibility, data-driven decisions

**Critical Path:**
1. Database schema (BH-001, BH-002) → 8 hours
2. Core services (BH-003, BH-004) → 40 hours
3. Controllers (BH-005, BH-006) → 16 hours
4. Frontend (BH-008) → 20 hours
5. Testing (BH-009) → 16 hours
6. Scheduled jobs (BH-007) → 8 hours

---

## Section 2: Lead Qualification & Scoring

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** HIGH  
**Business Value:** Sales effectiveness, consistent qualification methodology  
**Dependencies:** None (works with existing opportunities table)  
**Estimated Effort:** 60-80 hours (1.5-2 weeks)

### Problem Statement

Opportunities flow from "lead" → "qualified" with no structured criteria. Sales team makes subjective decisions without consistent methodology or data-backed scoring.

### Tasks Breakdown

#### TASK 2.1: Database Schema - Qualification Fields
**ID:** LQ-001  
**Priority:** High  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Add BANT qualification fields to opportunities table.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_add_qualification_fields_to_opportunities.php`

**Implementation Details:**
```sql
ALTER TABLE opportunities
ADD COLUMN qualification_score INT DEFAULT 0 CHECK (qualification_score >= 0 AND qualification_score <= 100),
ADD COLUMN budget_confirmed VARCHAR(20) DEFAULT 'unknown' CHECK (budget_confirmed IN ('yes', 'no', 'unknown')),
ADD COLUMN authority_level VARCHAR(30) DEFAULT 'unknown' CHECK (authority_level IN ('decision_maker', 'influencer', 'user', 'unknown')),
ADD COLUMN need_validation VARCHAR(30) DEFAULT 'unknown' CHECK (need_validation IN ('critical', 'important', 'nice_to_have', 'unknown')),
ADD COLUMN timeline_urgency VARCHAR(30) DEFAULT 'unclear' CHECK (timeline_urgency IN ('immediate', 'this_quarter', 'next_quarter', 'unclear')),
ADD COLUMN strategic_fit VARCHAR(30) DEFAULT 'cold_lead' CHECK (strategic_fit IN ('existing_client', 'referral', 'target_industry', 'cold_lead')),
ADD COLUMN disqualification_reason TEXT NULL,
ADD COLUMN last_contact_date DATE NULL;

CREATE INDEX idx_opportunities_qualification_score ON opportunities(qualification_score DESC);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] All columns have COMMENT statements
- [ ] Check constraints work properly
- [ ] Index created for scoring queries

---

#### TASK 2.2: Service - LeadQualificationService
**ID:** LQ-002  
**Priority:** High  
**Effort:** 24 hours  
**Dependencies:** LQ-001

**Description:**  
Create service to calculate qualification score based on BANT criteria.

**Required Files:**
- Service: `app/Services/LeadQualificationService.php`

**Methods to Implement:**
```php
public function calculateQualificationScore(int $opportunityId): array
public function classifyLead(int $score): string // HOT/WARM/COLD
public function getRecommendation(int $opportunityId): array
public function updateQualificationScore(int $opportunityId, int $userId): array
```

**Scoring Algorithm:**
- Contract Value Score (25 points max)
- Strategic Fit Score (20 points max)
- Urgency Score (15 points max)
- Budget Authority Score (20 points max)
- Need Validation Score (20 points max)
- **Total: 100 points**

**Classification:**
- 70-100 points = HOT (auto-suggest qualified stage)
- 40-69 points = WARM (manual review)
- <40 points = COLD (nurture or disqualify)

**Success Criteria:**
- [ ] Service follows single responsibility principle
- [ ] Returns facts only (scores and breakdown)
- [ ] No other service injections
- [ ] Comprehensive scoring breakdown returned

**Architecture Notes:**
```php
// Return format
return [
    'score' => 78,
    'classification' => 'HOT',
    'breakdown' => [
        'contract_value' => ['points' => 25, 'reason' => 'High value (>$100K)'],
        'strategic_fit' => ['points' => 20, 'reason' => 'Existing client'],
        'urgency' => ['points' => 15, 'reason' => 'Immediate need'],
        'authority' => ['points' => 10, 'reason' => 'Influencer engaged'],
        'need' => ['points' => 5, 'reason' => 'Nice-to-have']
    ],
    'recommendation' => [
        'action' => 'qualify',
        'priority' => 'HIGH',
        'message' => 'This is a HOT lead - consider moving to qualified stage'
    ]
];
```

---

#### TASK 2.3: Controller - LeadQualificationController
**ID:** LQ-003  
**Priority:** High  
**Effort:** 8 hours  
**Dependencies:** LQ-002

**Description:**  
Create thin controller for lead qualification API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/LeadQualificationController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
GET /api/opportunities/{id}/qualification-score
PUT /api/opportunities/{id}/calculate-qualification
PUT /api/opportunities/{id}/update-bant
```

**Success Criteria:**
- [ ] Injects ONLY LeadQualificationService
- [ ] Thin pass-through pattern
- [ ] No calculations in controller
- [ ] Proper auth middleware

---

#### TASK 2.4: Frontend - Qualification UI
**ID:** LQ-004  
**Priority:** High  
**Effort:** 20 hours  
**Dependencies:** LQ-003

**Description:**  
Add BANT fields to opportunity forms and display qualification score.

**Required Files:**
- Update: `resources/views/opportunities/create.blade.php`
- Update: `resources/views/opportunities/edit.blade.php`
- Update: `resources/views/opportunities/show.blade.php`

**UI Elements:**
- BANT field dropdowns in create/edit forms
- Qualification score badge (color-coded by classification)
- Score breakdown modal (show point allocation)
- Recommendation display
- Last contact date tracker

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Alpine.js for data fetching only
- [ ] Real-time score recalculation on field change
- [ ] Color-coding: RED (cold), YELLOW (warm), GREEN (hot)

---

#### TASK 2.5: Testing - Lead Qualification Tests
**ID:** LQ-005  
**Priority:** High  
**Effort:** 16 hours  
**Dependencies:** LQ-002, LQ-003

**Description:**  
Create unit and integration tests for lead qualification.

**Required Files:**
- Test: `tests/Feature/LeadQualificationTest.php`

**Test Cases:**
```php
test_calculate_qualification_score_hot_lead()
test_calculate_qualification_score_warm_lead()
test_calculate_qualification_score_cold_lead()
test_qualification_API_requires_authentication()
test_bant_fields_validation()
test_score_breakdown_accuracy()
test_recommendation_logic()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Edge cases covered
- [ ] Code coverage >80%

---

### Section 2 Summary

**Total Tasks:** 5  
**Total Effort:** 72 hours (1.8 weeks)  
**Priority:** HIGH  
**Business Impact:** +15% close rate improvement, 20% faster sales cycle

---

## Section 3: Cost Determination for Negotiation

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** HIGH  
**Business Value:** Pricing intelligence, margin protection, negotiation support  
**Dependencies:** None  
**Estimated Effort:** 60-80 hours (1.5-2 weeks)

### Problem Statement

Opportunities track `estimated_value` (what we charge) but not `estimated_cost` (what it costs us to deliver). No visibility into profit margins during negotiation, no floor price, no data to support pricing decisions.

### Tasks Breakdown

#### TASK 3.1: Database Schema - Costing Fields
**ID:** CD-001  
**Priority:** High  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Add costing and pricing fields to opportunities table.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_add_costing_fields_to_opportunities.php`

**Implementation Details:**
```sql
ALTER TABLE opportunities
ADD COLUMN estimated_cost NUMERIC(15,2) DEFAULT 0 CHECK (estimated_cost >= 0),
ADD COLUMN estimated_hours NUMERIC(10,2) DEFAULT 0 CHECK (estimated_hours >= 0),
ADD COLUMN pricing_model VARCHAR(30) DEFAULT 'fixed_price' CHECK (pricing_model IN ('fixed_price', 'time_material', 'retainer', 'value_based')),
ADD COLUMN target_margin_percentage NUMERIC(5,2) DEFAULT 30.00 CHECK (target_margin_percentage >= 0 AND target_margin_percentage <= 100),
ADD COLUMN floor_price NUMERIC(15,2) NULL,
ADD COLUMN ceiling_price NUMERIC(15,2) NULL,
ADD COLUMN actual_margin_percentage NUMERIC(5,2) NULL,
ADD COLUMN labor_rate_assumed NUMERIC(10,2) DEFAULT 75.00;

COMMENT ON COLUMN opportunities.estimated_cost IS 'Internal cost to deliver (labor + materials + overhead)';
COMMENT ON COLUMN opportunities.floor_price IS 'Minimum acceptable price (walk-away point)';
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] All columns have COMMENT statements
- [ ] Check constraints validated
- [ ] Follows database compliance patterns

---

#### TASK 3.2: Service - OpportunityCostingService
**ID:** CD-002  
**Priority:** High  
**Effort:** 24 hours  
**Dependencies:** CD-001

**Description:**  
Create service for cost calculation and pricing strategies.

**Required Files:**
- Service: `app/Services/OpportunityCostingService.php`

**Methods to Implement:**
```php
public function calculateEstimatedCost(array $costingData): array
public function calculatePriceFromMargin(float $cost, float $marginPercentage): array
public function calculateMargin(float $price, float $cost): array
public function evaluateNegotiationOffer(int $opportunityId, float $proposedPrice): array
public function calculatePriceRange(float $cost, float $minMarginPct = 10, float $maxMarginPct = 50): array
```

**Success Criteria:**
- [ ] Service follows single responsibility
- [ ] Returns facts only (calculations)
- [ ] No decisions or business rules
- [ ] All formulas documented in PHPDoc

**Architecture Notes:**
```php
// Example return format
return [
    'labor_cost' => 30000.00,
    'materials_cost' => 2000.00,
    'overhead_cost' => 4800.00,
    'total_cost' => 36800.00,
    'floor_price' => 40480.00,     // 10% margin
    'target_price' => 47840.00,    // 30% margin
    'ceiling_price' => 55200.00,   // 50% margin
];
```

---

#### TASK 3.3: Controller - OpportunityCostingController
**ID:** CD-003  
**Priority:** High  
**Effort:** 8 hours  
**Dependencies:** CD-002

**Description:**  
Create thin controller for costing API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/OpportunityCostingController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
POST /api/opportunities/{id}/calculate-cost
POST /api/opportunities/{id}/calculate-price
GET /api/opportunities/{id}/negotiation-guidance
POST /api/opportunities/{id}/evaluate-offer
```

**Success Criteria:**
- [ ] Injects ONLY OpportunityCostingService
- [ ] Thin pass-through pattern
- [ ] No calculations in controller

---

#### TASK 3.4: Frontend - Negotiation Dashboard
**ID:** CD-004  
**Priority:** High  
**Effort:** 24 hours  
**Dependencies:** CD-003

**Description:**  
Create negotiation support UI with cost breakdown and pricing guidance.

**Required Files:**
- Blade View: `resources/views/opportunities/negotiation-dashboard.blade.php`

**UI Elements:**
- Cost analysis section (labor, materials, overhead)
- Pricing strategy section (floor, target, quoted, ceiling)
- Negotiation zone visualization (horizontal bar chart)
- Scenario analyzer ("What if client offers $X?")
- Margin calculator
- Historical comparison (if similar projects exist)

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Display data exactly as returned from API
- [ ] Real-time scenario evaluation
- [ ] Color-coded margin indicators

---

#### TASK 3.5: Integration - Link Opportunity Cost to Project Budget
**ID:** CD-005  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** CD-001, CD-002

**Description:**  
When opportunity is won and converted to project, copy cost baseline for variance tracking.

**Required Files:**
- Update: `app/Services/OpportunityProjectService.php`

**Implementation:**
```php
// In createProjectFromOpportunity method
$project = [
    'contract_value' => $opportunity->estimated_value,
    'total_budgeted_cost' => $opportunity->estimated_cost,  // NEW
    'estimated_hours' => $opportunity->estimated_hours,      // NEW
    'baseline_margin_percentage' => $opportunity->actual_margin_percentage, // NEW
    // ... existing fields
];
```

**Success Criteria:**
- [ ] Cost baseline copied from opportunity
- [ ] Enables future variance tracking (actual vs estimated)
- [ ] Audit trail logged
- [ ] No breaking changes to existing flow

---

#### TASK 3.6: Testing - Opportunity Costing Tests
**ID:** CD-006  
**Priority:** High  
**Effort:** 16 hours  
**Dependencies:** CD-002, CD-003

**Description:**  
Create tests for costing calculations and API endpoints.

**Required Files:**
- Test: `tests/Feature/OpportunityCostingTest.php`

**Test Cases:**
```php
test_calculate_estimated_cost()
test_calculate_price_from_margin()
test_calculate_margin()
test_evaluate_negotiation_offer_acceptable()
test_evaluate_negotiation_offer_below_floor()
test_calculate_price_range()
test_costing_API_requires_authentication()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Edge cases covered (zero cost, negative margin, etc.)
- [ ] Code coverage >80%

---

### Section 3 Summary

**Total Tasks:** 6  
**Total Effort:** 84 hours (2.1 weeks)  
**Priority:** HIGH  
**Business Impact:** 25% reduction in unprofitable projects, +8% average margin

---

## Section 5: Project Costing & Profitability (EVM)

**Status:** 🟡 PARTIALLY PLANNED  
**Priority:** MEDIUM  
**Business Value:** Real-time project profitability, cost variance detection  
**Dependencies:** Section 3 (Cost Determination)  
**Estimated Effort:** 100-120 hours (2.5-3 weeks)  
**Reference Document:** `PLANNED_PROJECT_COSTING_VALUATION.md` (exists but not implemented)

### Problem Statement

Currently track Earned Value (contract_value × progress) and Payment Gap (earned - received), but DON'T track:
- Actual costs per project
- Budgeted costs per task
- Cost Performance Index (CPI)
- Schedule Performance Index (SPI)
- Estimate at Completion (EAC)

### Tasks Breakdown

#### TASK 5.1: Database Schema - Project Budget Fields
**ID:** PC-001  
**Priority:** Medium  
**Effort:** 6 hours  
**Dependencies:** CD-001 (opportunities costing)

**Description:**  
Add budget tracking fields to projects, tasks, and milestones tables.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_add_budget_fields_to_projects.php`

**Implementation Details:**
```sql
-- Projects table
ALTER TABLE projects
ADD COLUMN total_budgeted_cost NUMERIC(15,2) DEFAULT 0,
ADD COLUMN actual_cost NUMERIC(15,2) DEFAULT 0,
ADD COLUMN estimated_hours NUMERIC(10,2) DEFAULT 0,
ADD COLUMN actual_hours NUMERIC(10,2) DEFAULT 0,
ADD COLUMN baseline_margin_percentage NUMERIC(5,2) NULL;

-- Tasks table
ALTER TABLE tasks
ADD COLUMN budgeted_cost NUMERIC(15,2) DEFAULT 0,
ADD COLUMN actual_cost NUMERIC(15,2) DEFAULT 0,
ADD COLUMN budgeted_hours NUMERIC(10,2) DEFAULT 0,
ADD COLUMN actual_hours NUMERIC(10,2) DEFAULT 0;

-- Milestones table
ALTER TABLE milestones
ADD COLUMN budgeted_cost NUMERIC(15,2) DEFAULT 0,
ADD COLUMN actual_cost NUMERIC(15,2) DEFAULT 0;
```

**Success Criteria:**
- [ ] Migrations run successfully
- [ ] All columns have COMMENT statements
- [ ] Follows data modeling guide patterns

---

#### TASK 5.2: Service - ProjectCostService
**ID:** PC-002  
**Priority:** Medium  
**Effort:** 20 hours  
**Dependencies:** PC-001

**Description:**  
Create service to calculate actual project costs from expenses and labor.

**Required Files:**
- Service: `app/Services/ProjectCostService.php`

**Methods to Implement:**
```php
public function calculateActualCost(int $projectId): array
public function calculateActualCostByTask(int $taskId): array
public function getCostBreakdown(int $projectId): array
public function calculateCostVariance(int $projectId): array
```

**Success Criteria:**
- [ ] Service follows single responsibility
- [ ] Calculates from expenses table
- [ ] Returns cost breakdown by category
- [ ] No other service injections

---

#### TASK 5.3: Service - EarnedValueService
**ID:** PC-003  
**Priority:** Medium  
**Effort:** 24 hours  
**Dependencies:** PC-002

**Description:**  
Create service to calculate EVM metrics (CPI, SPI, EAC, VAC).

**Required Files:**
- Service: `app/Services/EarnedValueService.php`

**Methods to Implement:**
```php
public function calculateEVM(int $projectId): array
public function calculateCPI(int $projectId): float
public function calculateSPI(int $projectId): float
public function calculateEAC(int $projectId): float
public function calculateVAC(int $projectId): float
public function getPerformanceStatus(int $projectId): array
```

**EVM Formulas:**
- **CPI** (Cost Performance Index) = EV / AC
- **SPI** (Schedule Performance Index) = EV / PV
- **EAC** (Estimate at Completion) = Budget / CPI
- **VAC** (Variance at Completion) = Budget - EAC

**Success Criteria:**
- [ ] All EVM calculations accurate
- [ ] Returns performance interpretation
- [ ] Handles edge cases (zero AC, zero PV)

**Architecture Notes:**
```php
// Return format
return [
    'earned_value' => 637500.00,   // contract_value × progress
    'actual_cost' => 550000.00,    // from ProjectCostService
    'planned_value' => 750000.00,  // budget × planned_progress
    'cpi' => 1.16,                 // 16% under budget
    'spi' => 0.85,                 // 15% behind schedule
    'eac' => 646551.72,            // projected final cost
    'vac' => 103448.28,            // projected profit
    'status' => 'under_budget_behind_schedule'
];
```

---

#### TASK 5.4: Controller - ProjectCostController
**ID:** PC-004  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** PC-002, PC-003

**Description:**  
Create controller for project costing API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/ProjectCostController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
GET /api/projects/{id}/actual-cost
GET /api/projects/{id}/cost-breakdown
GET /api/projects/{id}/cost-variance
GET /api/projects/{id}/evm
GET /api/projects/{id}/performance-status
```

**Success Criteria:**
- [ ] Injects ONLY ProjectCostService or EarnedValueService
- [ ] Thin pass-through pattern
- [ ] No calculations in controller

---

#### TASK 5.5: Frontend - Project Profitability Dashboard
**ID:** PC-005  
**Priority:** Medium  
**Effort:** 24 hours  
**Dependencies:** PC-004

**Description:**  
Create dashboard view for project cost/profitability tracking.

**Required Files:**
- Blade View: `resources/views/projects/profitability.blade.php`

**UI Elements:**
- Budget vs Actual comparison chart
- CPI and SPI gauges with color-coding
- EAC projection with profit/loss indicator
- Cost breakdown by category (labor, materials, overhead)
- Variance analysis table
- Trend charts (cost over time)

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Display data exactly as returned from API
- [ ] Real-time updates when expenses added
- [ ] Color-coded performance status

---

#### TASK 5.6: Integration - Expense to Cost Linking
**ID:** PC-006  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** PC-001

**Description:**  
Link expenses to project actual_cost automatically.

**Required Files:**
- Update: `app/Services/ExpenseManagementService.php` (if exists)
- Or: Create trigger in migration

**Implementation:**
When expense is created/updated/deleted for a project:
1. Recalculate project actual_cost
2. Update project.actual_cost field
3. Trigger CPI recalculation if needed

**Success Criteria:**
- [ ] Expenses automatically update project costs
- [ ] Real-time cost tracking
- [ ] Audit trail maintained

---

#### TASK 5.7: Testing - Project Costing Tests
**ID:** PC-007  
**Priority:** Medium  
**Effort:** 20 hours  
**Dependencies:** PC-002, PC-003, PC-004

**Description:**  
Create comprehensive tests for project costing and EVM.

**Required Files:**
- Test: `tests/Feature/ProjectCostingTest.php`

**Test Cases:**
```php
test_calculate_actual_cost_from_expenses()
test_calculate_cost_breakdown()
test_calculate_cost_variance()
test_calculate_cpi()
test_calculate_spi()
test_calculate_eac()
test_calculate_vac()
test_performance_status_classification()
test_evm_api_endpoints()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Edge cases covered
- [ ] Code coverage >80%

---

### Section 5 Summary

**Total Tasks:** 7  
**Total Effort:** 110 hours (2.75 weeks)  
**Priority:** MEDIUM  
**Business Impact:** Real-time profitability tracking, 40% earlier budget overrun detection

---

## Section 6: 24/7 Operational Command Center

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** MEDIUM  
**Business Value:** Proactive decision support, strategic visibility  
**Dependencies:** Sections 1-3, 5 (needs metrics and patterns)  
**Estimated Effort:** 160-200 hours (4-5 weeks)

### Problem Statement

Transform from passive analytics dashboard → active decision engine that monitors business health continuously, surfaces priority actions, predicts issues, and prescribes solutions.

### Tasks Breakdown

#### TASK 6.1: Database Schema - Insights Table
**ID:** OC-001  
**Priority:** Medium  
**Effort:** 4 hours  
**Dependencies:** BH-001

**Description:**  
Create `insights` table to store generated recommendations and action items.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_insights_table.php`

**Implementation Details:**
```sql
CREATE TABLE insights (
    id SERIAL PRIMARY KEY,
    type VARCHAR(50) NOT NULL CHECK (type IN ('action_required', 'opportunity', 'risk', 'pattern')),
    priority VARCHAR(20) NOT NULL CHECK (priority IN ('critical', 'high', 'medium', 'low')),
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    recommended_action TEXT NULL,
    impact_estimate JSON NULL,
    confidence_level NUMERIC(3,2) NULL CHECK (confidence_level >= 0 AND confidence_level <= 1),
    source VARCHAR(50) NOT NULL,
    dismissed_at TIMESTAMP WITH TIME ZONE NULL,
    dismissed_by INT REFERENCES users(id) NULL,
    actioned_at TIMESTAMP WITH TIME ZONE NULL,
    actioned_by INT REFERENCES users(id) NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    expires_at TIMESTAMP WITH TIME ZONE NULL
);

CREATE INDEX idx_insights_active ON insights(created_at, dismissed_at) WHERE dismissed_at IS NULL;
CREATE INDEX idx_insights_priority ON insights(priority, created_at);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] All columns have COMMENT statements
- [ ] Indexes created for performance

---

#### TASK 6.2: Database Schema - Historical Patterns Table
**ID:** OC-002  
**Priority:** Medium  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Create `historical_patterns` table to store learned patterns for AI.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_historical_patterns_table.php`

**Implementation Details:**
```sql
CREATE TABLE historical_patterns (
    id SERIAL PRIMARY KEY,
    pattern_type VARCHAR(100) NOT NULL,
    condition JSON NOT NULL,
    observed_outcome VARCHAR(100) NOT NULL,
    outcome_value NUMERIC(15,2) NULL,
    sample_size INT NOT NULL,
    confidence_level NUMERIC(3,2) NOT NULL,
    date_range_start DATE NOT NULL,
    date_range_end DATE NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE INDEX idx_patterns_type ON historical_patterns(pattern_type);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] JSON column for flexible condition storage
- [ ] Database compliance verified

---

#### TASK 6.3: Service - PatternRecognitionService
**ID:** OC-003  
**Priority:** Medium  
**Effort:** 32 hours  
**Dependencies:** OC-002

**Description:**  
Create service to analyze historical data and identify patterns.

**Required Files:**
- Service: `app/Services/PatternRecognitionService.php`

**Methods to Implement:**
```php
public function analyzePatterns(string $patternType): array
public function applyPatterns(int $entityId, string $entityType): array
public function updatePatterns(): void
public function getOpportunityPatterns(): array
public function getProjectDeliveryPatterns(): array
public function getCashFlowPatterns(): array
```

**Pattern Examples:**
- "Opportunities in qualified stage >14 days → 40% drop in close rate"
- "PHI <70 at 50% completion → 95% exceed budget"
- "Projects with weekly updates → 30% higher PHI"

**Success Criteria:**
- [ ] Service follows single responsibility
- [ ] Statistical analysis of historical data
- [ ] Returns patterns with confidence levels
- [ ] No other service injections

**Architecture Notes:**
```php
// Example pattern return
return [
    'pattern_type' => 'opportunity_conversion',
    'condition' => ['stage' => 'qualified', 'days' => '>14'],
    'observed_outcome' => 'close_rate_drops_40_percent',
    'confidence_level' => 0.85,
    'sample_size' => 47,
    'recommendation' => 'Follow up urgently on Opportunity #7 (16 days in qualified)'
];
```

---

#### TASK 6.4: Service - InsightGenerationService
**ID:** OC-004  
**Priority:** Medium  
**Effort:** 32 hours  
**Dependencies:** OC-001, OC-003, BH-003

**Description:**  
Create service to generate actionable insights from rules and patterns.

**Required Files:**
- Service: `app/Services/InsightGenerationService.php`

**Methods to Implement:**
```php
public function generateAllInsights(): array
public function generateActionInsights(): array
public function generateOpportunityInsights(): array
public function generateRiskInsights(): array
public function dismissInsight(int $insightId, int $userId): void
public function markInsightActioned(int $insightId, int $userId): void
```

**Insight Categories:**
1. **Action Required** (critical deadline, budget crisis)
2. **Opportunities** (upsell, re-engagement, referral request)
3. **Risks** (cash shortfall, project delay, low qualification score)
4. **Patterns** (historical learning application)

**Success Criteria:**
- [ ] Service follows single responsibility
- [ ] Rules-based insight generation (Layer 1)
- [ ] Pattern-based insights (Layer 2)
- [ ] Stores insights in database
- [ ] Deduplication logic (don't create duplicate insights)

---

#### TASK 6.5: Service - CommandCenterService
**ID:** OC-005  
**Priority:** Medium  
**Effort:** 24 hours  
**Dependencies:** OC-004, BH-003, BH-004

**Description:**  
Create service to aggregate command center dashboard data.

**Required Files:**
- Service: `app/Services/CommandCenterService.php`

**Methods to Implement:**
```php
public function getDashboardData(): array
public function calculateBusinessHealth(): array
public function getPriorityActions(): array
public function getOpportunities(): array
public function getGoalProgress(): array
public function generateDailyBriefing(): array
```

**Success Criteria:**
- [ ] Service can inject multiple READ-ONLY services (BusinessMetricsService, GoalTrackingService, InsightGenerationService)
- [ ] Aggregates data from multiple sources
- [ ] Returns unified dashboard structure
- [ ] No calculations (delegates to other services)

**Architecture Notes:**
This is a **synthesis service** - exception to single-service rule because it aggregates read-only data from multiple sources for dashboard display.

```php
public function __construct(
    private BusinessMetricsService $metricsService,
    private GoalTrackingService $goalService,
    private InsightGenerationService $insightService
) {}
```

---

#### TASK 6.6: Controller - CommandCenterController
**ID:** OC-006  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** OC-005

**Description:**  
Create controller for command center API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/CommandCenterController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
GET /api/command-center/dashboard
GET /api/command-center/business-health
GET /api/command-center/priority-actions
GET /api/command-center/opportunities
GET /api/command-center/goal-progress
POST /api/insights/{id}/dismiss
POST /api/insights/{id}/action
```

**Success Criteria:**
- [ ] Injects ONLY CommandCenterService
- [ ] Thin pass-through pattern
- [ ] No calculations or transformations

---

#### TASK 6.7: Scheduled Job - InsightGenerationJob
**ID:** OC-007  
**Priority:** Medium  
**Effort:** 8 hours  
**Dependencies:** OC-004

**Description:**  
Create scheduled job to generate insights hourly/daily.

**Required Files:**
- Job: `app/Console/Commands/GenerateInsights.php`
- Schedule: Update `app/Console/Kernel.php`

**Implementation:**
```php
// Runs every 4 hours
public function handle()
{
    $insights = $this->insightService->generateAllInsights();
    
    Log::info('Insights generated', [
        'count' => count($insights),
        'types' => array_count_values(array_column($insights, 'type'))
    ]);
}
```

**Success Criteria:**
- [ ] Runs every 4 hours via scheduler
- [ ] Generates action, opportunity, and risk insights
- [ ] Logs completion
- [ ] Deduplicates existing insights

---

#### TASK 6.8: Scheduled Job - DailyBriefingJob
**ID:** OC-008  
**Priority:** Low  
**Effort:** 8 hours  
**Dependencies:** OC-005

**Description:**  
Create job to send daily briefing email to leadership.

**Required Files:**
- Job: `app/Console/Commands/SendDailyBriefing.php`
- Mail: `app/Mail/DailyBriefingMail.php`
- Schedule: Update `app/Console/Kernel.php`

**Implementation:**
Sends email at 6 AM daily with:
- Business health score
- Critical actions required
- Opportunities to seize
- Key metrics summary

**Success Criteria:**
- [ ] Runs at 6 AM daily
- [ ] Generates email from CommandCenterService
- [ ] Sends to configured recipients
- [ ] Handles send failures gracefully

---

#### TASK 6.9: Frontend - Command Center Dashboard
**ID:** OC-009  
**Priority:** High  
**Effort:** 40 hours  
**Dependencies:** OC-006

**Description:**  
Create comprehensive command center UI.

**Required Files:**
- Blade View: `resources/views/command-center/dashboard.blade.php`

**UI Sections:**
1. **Business Health Score** (0-100 gauge)
2. **Critical Systems** (Cash runway, Pipeline, Projects, Margin)
3. **Priority Actions** (sorted by priority with action buttons)
4. **Opportunities to Seize** (upsell, re-engagement, proposals)
5. **Goal Progress** (Q1 revenue, projects, margin, clients)
6. **Pattern Insights** (historical learnings applied to current state)

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Alpine.js for data fetching only
- [ ] Real-time data updates
- [ ] Action buttons trigger API calls
- [ ] Dismiss/snooze functionality
- [ ] Responsive design

---

#### TASK 6.10: Testing - Command Center Tests
**ID:** OC-010  
**Priority:** Medium  
**Effort:** 24 hours  
**Dependencies:** OC-004, OC-005, OC-006

**Description:**  
Create tests for command center functionality.

**Required Files:**
- Test: `tests/Feature/CommandCenterTest.php`

**Test Cases:**
```php
test_generate_action_insights()
test_generate_opportunity_insights()
test_generate_risk_insights()
test_calculate_business_health()
test_get_priority_actions()
test_dismiss_insight()
test_mark_insight_actioned()
test_command_center_API_requires_auth()
test_daily_briefing_generation()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Code coverage >75%
- [ ] Edge cases handled

---

### Section 6 Summary

**Total Tasks:** 10  
**Total Effort:** 184 hours (4.6 weeks)  
**Priority:** MEDIUM  
**Business Impact:** 24/7 monitoring, proactive decision support, 40% faster routine decisions

---

## Section 7: Smart Business Assistant (AI Agent)

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** LOW (Advanced feature)  
**Business Value:** Conversational strategic assistant, natural language queries  
**Dependencies:** Sections 1-6 (needs complete data and insights)  
**Estimated Effort:** 200-240 hours (5-6 weeks)

### Problem Statement

Currently, users must navigate multiple dashboards and run reports manually. No conversational interface to ask strategic questions like "How do we hit Q1 revenue goal?" or "Why did we lose Opportunity #9?"

### Tasks Breakdown

#### TASK 7.1: Research & Setup - Choose LLM Provider
**ID:** AI-001  
**Priority:** Low  
**Effort:** 8 hours  
**Dependencies:** None

**Description:**  
Research and select LLM provider (OpenAI GPT-4, Anthropic Claude, or open-source).

**Evaluation Criteria:**
- Cost per query
- Response quality
- Context window size (need large context for business data)
- API reliability
- Data privacy compliance

**Deliverables:**
- [ ] Provider selected and documented
- [ ] API account created
- [ ] Rate limits and costs documented
- [ ] Budget approval obtained

---

#### TASK 7.2: Service - AIAgentService
**ID:** AI-002  
**Priority:** Low  
**Effort:** 40 hours  
**Dependencies:** AI-001, OC-005, BH-003

**Description:**  
Create service to process natural language queries with LLM.

**Required Files:**
- Service: `app/Services/AIAgentService.php`

**Methods to Implement:**
```php
public function processQuery(string $query, int $userId): array
private function gatherContext(): array
private function buildPrompt(string $query, array $context): string
private function callLLM(string $prompt): array
private function extractActions(array $response): array
public function executeAction(string $action, array $parameters, int $userId): array
```

**Context Gathering:**
Inject multiple READ-ONLY services to gather complete business context:
- BusinessMetricsService
- CommandCenterService
- PatternRecognitionService
- GoalTrackingService

**Success Criteria:**
- [ ] Service can inject multiple services (synthesis service exception)
- [ ] Gathers complete business context
- [ ] Builds effective prompts with context
- [ ] Parses LLM responses
- [ ] Extracts actionable items
- [ ] Handles API errors gracefully

**Architecture Notes:**
```php
// Return format
return [
    'response' => 'You are at $250K of $300K (83%). Here is the path to $300K...',
    'data' => [
        'current_revenue' => 250000,
        'goal' => 300000,
        'gap' => 50000
    ],
    'suggested_actions' => [
        ['action' => 'prioritize_opportunity', 'opportunity_id' => 12, 'estimated_impact' => 45000],
        ['action' => 'review_pipeline', 'target_close_rate' => 0.6]
    ]
];
```

---

#### TASK 7.3: Controller - AIAgentController
**ID:** AI-003  
**Priority:** Low  
**Effort:** 8 hours  
**Dependencies:** AI-002

**Description:**  
Create controller for AI agent API endpoints.

**Required Files:**
- Controller: `app/Http/Controllers/AIAgentController.php`
- Routes: Update `routes/api.php`

**Endpoints:**
```php
POST /api/ai/query
GET /api/ai/suggestions
POST /api/ai/execute-action
GET /api/ai/conversation-history
```

**Success Criteria:**
- [ ] Injects ONLY AIAgentService
- [ ] Thin pass-through pattern
- [ ] Rate limiting applied (prevent abuse)

---

#### TASK 7.4: Frontend - AI Chat Interface
**ID:** AI-004  
**Priority:** Low  
**Effort:** 32 hours  
**Dependencies:** AI-003

**Description:**  
Create conversational chat UI for AI agent.

**Required Files:**
- Blade Component: `resources/views/components/ai-chat.blade.php`
- Alpine.js: Embedded in component

**UI Elements:**
- Chat modal (toggleable from any page)
- Message history display
- Input field with send button
- Suggested questions
- Action buttons (when AI recommends actions)
- Thinking indicator (while AI processes)

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Alpine.js for data fetching only
- [ ] Real-time message streaming (if supported)
- [ ] Conversation history persisted
- [ ] Action confirmation before execution

---

#### TASK 7.5: Service - Action Execution Safety Layer
**ID:** AI-005  
**Priority:** Low  
**Effort:** 24 hours  
**Dependencies:** AI-002

**Description:**  
Create service to safely execute AI-recommended actions.

**Required Files:**
- Service: `app/Services/AIActionExecutionService.php`

**Safe Actions (Auto-executable):**
- Send payment reminder
- Flag opportunity for follow-up
- Create follow-up task
- Schedule reminder
- Generate report

**Unsafe Actions (Require Confirmation):**
- Create opportunity
- Update pricing
- Approve/reject
- Delete anything
- Financial commitments

**Methods:**
```php
public function executeAction(string $action, array $parameters, int $userId): array
public function isActionSafe(string $action): bool
public function requiresConfirmation(string $action): bool
public function rollbackAction(int $actionLogId): array
```

**Success Criteria:**
- [ ] Whitelist of safe actions
- [ ] Confirmation required for risky actions
- [ ] Rollback capability
- [ ] Audit logging of all AI actions
- [ ] Permission checks before execution

---

#### TASK 7.6: Database Schema - AI Conversation History
**ID:** AI-006  
**Priority:** Low  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Create table to store AI conversation history for continuous learning.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_ai_conversations_table.php`

**Implementation:**
```sql
CREATE TABLE ai_conversations (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) NOT NULL,
    query TEXT NOT NULL,
    response TEXT NOT NULL,
    context_snapshot JSON NULL,
    actions_suggested JSON NULL,
    actions_executed JSON NULL,
    feedback VARCHAR(20) CHECK (feedback IN ('helpful', 'not_helpful', 'incorrect', NULL)),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE INDEX idx_ai_conversations_user ON ai_conversations(user_id, created_at);
```

**Success Criteria:**
- [ ] Migration runs successfully
- [ ] Stores full conversation context
- [ ] Feedback tracking for learning

---

#### TASK 7.7: Feature - Proactive AI Suggestions
**ID:** AI-007  
**Priority:** Low  
**Effort:** 24 hours  
**Dependencies:** AI-002, OC-004

**Description:**  
AI agent proactively suggests actions based on insights.

**Implementation:**
When user logs in, AI analyzes:
- Priority actions from command center
- Current context (time of day, day of week)
- User role and responsibilities
- Recent activity patterns

Generates: "Good morning! Before you start your day: [3 key priorities]"

**Success Criteria:**
- [ ] Proactive suggestions on login
- [ ] Context-aware recommendations
- [ ] User can dismiss or snooze
- [ ] Learning from user behavior

---

#### TASK 7.8: Testing - AI Agent Tests
**ID:** AI-008  
**Priority:** Low  
**Effort:** 32 hours  
**Dependencies:** AI-002, AI-003, AI-005

**Description:**  
Create tests for AI agent functionality.

**Required Files:**
- Test: `tests/Feature/AIAgentTest.php`

**Test Cases:**
```php
test_process_query_with_valid_context()
test_gather_business_context()
test_extract_actions_from_response()
test_execute_safe_action()
test_unsafe_action_requires_confirmation()
test_conversation_history_stored()
test_AI_API_rate_limiting()
test_feedback_tracking()
```

**Success Criteria:**
- [ ] All tests pass
- [ ] Mock LLM responses for testing
- [ ] Code coverage >75%

---

#### TASK 7.9: Optimization - Prompt Engineering
**ID:** AI-009  
**Priority:** Low  
**Effort:** 16 hours  
**Dependencies:** AI-002, AI-004

**Description:**  
Optimize prompts for accurate and helpful responses.

**Activities:**
- Test with various business scenarios
- Refine system prompts
- Add examples (few-shot learning)
- Optimize token usage
- Test edge cases

**Success Criteria:**
- [ ] Query success rate >85%
- [ ] Response relevance validated
- [ ] Cost per query optimized
- [ ] User satisfaction >80%

---

#### TASK 7.10: Documentation - AI Agent User Guide
**ID:** AI-010  
**Priority:** Low  
**Effort:** 8 hours  
**Dependencies:** AI-004

**Description:**  
Create user documentation for AI agent features.

**Deliverables:**
- User guide with example queries
- Best practices for asking questions
- Explanation of capabilities and limitations
- Privacy and data usage policy
- Feedback mechanism instructions

**Success Criteria:**
- [ ] Comprehensive documentation
- [ ] Example queries for common scenarios
- [ ] Clear limitation disclosure
- [ ] Training materials created

---

### Section 7 Summary

**Total Tasks:** 10  
**Total Effort:** 196 hours (4.9 weeks)  
**Priority:** LOW (Advanced feature)  
**Business Impact:** Strategic assistant, natural language queries, 60% reduction in content creation time

**Notes:**
- High development complexity
- Requires LLM API budget ($500-$2000/month)
- Ongoing prompt optimization needed
- Consider after Sections 1-6 complete

---

## Section 8: Marketing Copilot

**Status:** ❌ NOT IMPLEMENTED  
**Priority:** LOW (New business unit)  
**Business Value:** Complete marketing-to-sales pipeline, lead generation automation  
**Dependencies:** Section 2 (Lead Qualification)  
**Estimated Effort:** 180-220 hours (4.5-5.5 weeks)

### Problem Statement

System currently starts at "Opportunities" (sales stage). Missing the entire marketing funnel: lead generation, capture, nurturing, and conversion to sales pipeline.

### Tasks Breakdown

#### TASK 8.1: Database Schema - Marketing Sources & Leads
**ID:** MC-001  
**Priority:** Low  
**Effort:** 6 hours  
**Dependencies:** None

**Description:**  
Create tables for marketing attribution and lead management.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_marketing_tables.php`

**Tables:**
1. `marketing_sources` (Website, LinkedIn, Referrals, etc.)
2. `leads` (pre-opportunity contacts)
3. `marketing_campaigns`
4. `campaign_metrics`

**Success Criteria:**
- [ ] All 4 tables created
- [ ] Foreign keys and constraints
- [ ] Indexes for performance
- [ ] COMMENT statements complete

---

#### TASK 8.2: Database Schema - Nurture Sequences
**ID:** MC-002  
**Priority:** Low  
**Effort:** 4 hours  
**Dependencies:** MC-001

**Description:**  
Create tables for automated email nurturing.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_nurture_tables.php`

**Tables:**
1. `nurture_sequences`
2. `nurture_emails`
3. `nurture_queue`

**Success Criteria:**
- [ ] Email automation schema complete
- [ ] Scheduled send tracking
- [ ] Open/click tracking support

---

#### TASK 8.3: Database Schema - Content & Social
**ID:** MC-003  
**Priority:** Low  
**Effort:** 4 hours  
**Dependencies:** None

**Description:**  
Create tables for content library and social media management.

**Required Files:**
- Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_content_social_tables.php`

**Tables:**
1. `marketing_content` (case studies, blogs, etc.)
2. `social_posts`

**Success Criteria:**
- [ ] Content library ready
- [ ] Social scheduling support
- [ ] Performance tracking fields

---

#### TASK 8.4: Service - LeadManagementService
**ID:** MC-004  
**Priority:** Low  
**Effort:** 20 hours  
**Dependencies:** MC-001

**Description:**  
Create service for lead capture and scoring.

**Required Files:**
- Service: `app/Services/LeadManagementService.php`

**Methods:**
```php
public function captureLead(array $data, int $sourceId): array
public function calculateMarketingScore(int $leadId): array
public function getLeadsByScore(int $minScore): array
public function assignLeadToSales(int $leadId, int $userId): array
```

**Success Criteria:**
- [ ] Lead scoring algorithm (engagement + fit)
- [ ] Auto-assignment rules
- [ ] Single responsibility

---

#### TASK 8.5: Service - LeadConversionService
**ID:** MC-005  
**Priority:** Low  
**Effort:** 16 hours  
**Dependencies:** MC-004, LQ-002

**Description:**  
Create service to convert qualified leads to opportunities.

**Required Files:**
- Service: `app/Services/LeadConversionService.php`

**Methods:**
```php
public function convertToOpportunity(int $leadId, int $userId): array
private function convertMarketingToSalesScore(int $marketingScore): int
private function determineStrategicFit($lead): string
public function stopNurtureSequences(int $leadId): void
```

**Success Criteria:**
- [ ] Seamless lead→opportunity conversion
- [ ] Marketing score→sales score mapping
- [ ] Stops nurture sequences on conversion
- [ ] Maintains attribution

---

#### TASK 8.6: Service - CampaignManagementService
**ID:** MC-006  
**Priority:** Low  
**Effort:** 20 hours  
**Dependencies:** MC-001

**Description:**  
Create service for campaign tracking and ROI calculation.

**Required Files:**
- Service: `app/Services/CampaignManagementService.php`

**Methods:**
```php
public function createCampaign(array $data, int $userId): array
public function updateCampaignMetrics(int $campaignId, array $metrics): void
public function calculateCampaignROI(int $campaignId): array
public function getTopPerformingCampaigns(int $limit = 5): array
```

**Success Criteria:**
- [ ] Full campaign lifecycle
- [ ] ROI calculations
- [ ] Attribution tracking

---

#### TASK 8.7: Service - NurtureSequenceService
**ID:** MC-007  
**Priority:** Low  
**Effort:** 24 hours  
**Dependencies:** MC-002, MC-004

**Description:**  
Create service for automated email nurturing.

**Required Files:**
- Service: `app/Services/NurtureSequenceService.php`

**Methods:**
```php
public function enrollLeadInSequence(int $leadId, int $sequenceId): void
public function scheduleSequenceEmails(int $leadId, int $sequenceId): array
public function sendScheduledEmails(): array
public function trackEmailOpen(int $queueId): void
public function trackEmailClick(int $queueId): void
public function pauseSequence(int $leadId, int $sequenceId): void
```

**Success Criteria:**
- [ ] Automated email scheduling
- [ ] Open/click tracking
- [ ] Pause/resume functionality

---

#### TASK 8.8: Service - ContentLibraryService
**ID:** MC-008  
**Priority:** Low  
**Effort:** 16 hours  
**Dependencies:** MC-003

**Description:**  
Create service for marketing content management.

**Required Files:**
- Service: `app/Services/ContentLibraryService.php`

**Methods:**
```php
public function createContent(array $data, int $userId): array
public function generateCaseStudy(int $projectId): array
public function getContentByTag(array $tags): array
public function trackContentPerformance(int $contentId, string $metric): void
```

**Success Criteria:**
- [ ] Content CRUD operations
- [ ] Performance tracking
- [ ] Tag-based search

---

#### TASK 8.9: Controllers - Marketing API Endpoints
**ID:** MC-009  
**Priority:** Low  
**Effort:** 16 hours  
**Dependencies:** MC-004, MC-005, MC-006, MC-007, MC-008

**Description:**  
Create controllers for all marketing services.

**Required Files:**
- `app/Http/Controllers/LeadManagementController.php`
- `app/Http/Controllers/CampaignManagementController.php`
- `app/Http/Controllers/NurtureSequenceController.php`
- `app/Http/Controllers/ContentLibraryController.php`
- Routes: Update `routes/api.php`

**Success Criteria:**
- [ ] All controllers inject single service
- [ ] Thin pass-through pattern
- [ ] Proper auth and permissions

---

#### TASK 8.10: Frontend - Marketing Dashboard
**ID:** MC-010  
**Priority:** Low  
**Effort:** 40 hours  
**Dependencies:** MC-009

**Description:**  
Create comprehensive marketing management UI.

**Required Files:**
- Multiple Blade views for leads, campaigns, content, social

**UI Pages:**
1. Leads list and detail pages
2. Campaign dashboard with ROI metrics
3. Nurture sequence builder
4. Content library
5. Social media scheduler
6. Attribution reports

**Success Criteria:**
- [ ] No calculations in frontend
- [ ] Alpine.js for data only
- [ ] Responsive design
- [ ] Complete workflow support

---

#### TASK 8.11: Integration - Projects to Marketing Content
**ID:** MC-011  
**Priority:** Low  
**Effort:** 8 hours  
**Dependencies:** MC-008

**Description:**  
Auto-suggest marketing opportunities when projects complete.

**Required Files:**
- New Service: `app/Services/ProjectMarketingService.php`

**Implementation:**
When project status changes to "completed" and PHI ≥85:
1. Create insight: "Case study opportunity - Project #X"
2. Suggest testimonial request
3. Offer AI-generated case study draft

**Success Criteria:**
- [ ] Automatic suggestions on completion
- [ ] AI case study generation
- [ ] Client approval workflow

---

#### TASK 8.12: Testing - Marketing Copilot Tests
**ID:** MC-012  
**Priority:** Low  
**Effort:** 32 hours  
**Dependencies:** MC-004 through MC-011

**Description:**  
Create comprehensive tests for marketing features.

**Required Files:**
- `tests/Feature/LeadManagementTest.php`
- `tests/Feature/CampaignManagementTest.php`
- `tests/Feature/NurtureSequenceTest.php`
- `tests/Feature/ContentLibraryTest.php`

**Success Criteria:**
- [ ] All tests pass
- [ ] Code coverage >75%
- [ ] Integration workflows tested

---

### Section 8 Summary

**Total Tasks:** 12  
**Total Effort:** 206 hours (5.15 weeks)  
**Priority:** LOW (New business unit)  
**Business Impact:** Complete marketing automation, +50% lead volume, <30% cost per lead reduction

**Notes:**
- Requires email service integration (Mailgun/SendGrid)
- May need social media API integrations
- Consider LLM for AI content generation
- Implement after core sales/delivery features stable

---

## Dependency Graph

```
Phase 1: Foundation (No Dependencies)
├─ 1.1 Business Health KPIs (BH-001 → BH-009)
│  └─ Enables: Section 6 (Command Center)
│
├─ 1.2 Lead Qualification (LQ-001 → LQ-005)
│  └─ Enables: Section 8 (Marketing Copilot)
│
└─ 1.3 Cost Determination (CD-001 → CD-006)
   └─ Enables: Section 5 (Project Costing)

Phase 2: Project Intelligence (Depends on Phase 1.3)
└─ Section 5: Project Costing & EVM (PC-001 → PC-007)
   └─ Requires: CD-001 (opportunity costing)
   └─ Enables: Section 6 (Command Center profitability insights)

Phase 3: Decision Support (Depends on Phases 1 & 2)
└─ Section 6: Command Center (OC-001 → OC-010)
   └─ Requires: BH-003, BH-004, PC-003
   └─ Enables: Section 7 (AI Agent context)

Phase 4: Advanced AI (Depends on Phases 1-3)
└─ Section 7: AI Agent (AI-001 → AI-010)
   └─ Requires: OC-005 (CommandCenterService)
   └─ Requires: Complete business context from all sections

Phase 5: Marketing (Parallel to Phase 4)
└─ Section 8: Marketing Copilot (MC-001 → MC-012)
   └─ Requires: LQ-002 (LeadQualificationService)
   └─ Can be built in parallel with AI Agent
```

---

## Recommended Implementation Sequence

### Priority Order (By Business Value & Dependencies)

#### PHASE 1: Foundation & Quick Wins (6-8 weeks)
**High Priority - Immediate Business Value**

1. **Lead Qualification & Costing** (Weeks 1-4)
   - Section 2: Lead Qualification (LQ-001 → LQ-005) - 72 hours
   - Section 3: Cost Determination (CD-001 → CD-006) - 84 hours
   - **Business Impact:** Sales effectiveness, margin protection
   - **ROI:** +15% close rate, 25% reduction in unprofitable projects
   - **Critical Path:** These work with existing data, no dependencies

2. **Business Health KPIs** (Weeks 3-6)
   - Section 1: Business Metrics (BH-001 → BH-009) - 108 hours
   - **Business Impact:** Executive visibility
   - **ROI:** Data-driven decisions, strategic planning
   - **Can overlap:** Start after Week 2 of lead qualification

---

#### PHASE 2: Project Intelligence (4-5 weeks)
**Medium Priority - Profitability Tracking**

3. **Project Costing & EVM** (Weeks 7-11)
   - Section 5: Project Costing (PC-001 → PC-007) - 110 hours
   - **Dependencies:** CD-001 (opportunity costing fields)
   - **Business Impact:** Real-time profitability, cost variance detection
   - **ROI:** 40% earlier budget overrun detection, +8% average margin

---

#### PHASE 3: Decision Support (6-8 weeks)
**Medium Priority - Proactive Monitoring**

4. **Operational Command Center** (Weeks 12-18)
   - Section 6: Command Center (OC-001 → OC-010) - 184 hours
   - **Dependencies:** BH-003, PC-003 (metrics and costing)
   - **Business Impact:** 24/7 monitoring, priority action surfacing
   - **ROI:** 40% faster routine decisions, proactive issue detection

---

#### PHASE 4: Advanced Features (Choose One or Both)

5A. **AI Agent** (8-10 weeks) - LOW PRIORITY
   - Section 7: AI Agent (AI-001 → AI-010) - 196 hours
   - **Dependencies:** Complete business context (all previous sections)
   - **Business Impact:** Strategic assistant, natural language queries
   - **ROI:** Conversational interface, time savings
   - **Cost:** $500-$2000/month LLM API fees

5B. **Marketing Copilot** (6-8 weeks) - LOW PRIORITY
   - Section 8: Marketing (MC-001 → MC-012) - 206 hours
   - **Dependencies:** LQ-002 (lead qualification)
   - **Business Impact:** Complete marketing automation
   - **ROI:** +50% lead volume, -30% cost per lead
   - **Note:** Can be built in parallel with AI Agent

---

### Timeline Summary

| Phase | Duration | Total Effort | Priority | Start After |
|-------|----------|--------------|----------|-------------|
| **Phase 1** | 6-8 weeks | 264 hours | HIGH | Immediately |
| **Phase 2** | 4-5 weeks | 110 hours | MEDIUM | Phase 1 Complete |
| **Phase 3** | 6-8 weeks | 184 hours | MEDIUM | Phase 2 Complete |
| **Phase 4A** | 8-10 weeks | 196 hours | LOW | Phase 3 Complete |
| **Phase 4B** | 6-8 weeks | 206 hours | LOW | After LQ-002 |
| **TOTAL** | 30-39 weeks | 960 hours | - | - |

---

## Resource Planning

### Recommended Team Composition

#### Minimum Viable Team (1 Full-Stack Developer)
- **Timeline:** 30-39 weeks (7.5-9.75 months)
- **Pros:** Lower cost, consistent architecture
- **Cons:** Long timeline, single point of failure
- **Best For:** Budget-constrained, not time-sensitive

#### Optimal Team (2 Developers)
- **Composition:**
  - 1 Senior Backend Developer (Laravel, PostgreSQL, services)
  - 1 Frontend Developer (Alpine.js, Tailwind, UI/UX)
- **Timeline:** 18-22 weeks (4.5-5.5 months)
- **Pros:** Parallel work, faster delivery, specialization
- **Cons:** Higher cost, coordination needed
- **Best For:** Balanced cost/speed

#### Accelerated Team (3 Developers)
- **Composition:**
  - 1 Senior Backend Developer (architecture, complex services)
  - 1 Mid Backend Developer (CRUD services, controllers, testing)
  - 1 Frontend Developer (all UI work)
- **Timeline:** 12-16 weeks (3-4 months)
- **Pros:** Fastest delivery, parallel execution
- **Cons:** Highest cost, requires coordination
- **Best For:** Time-sensitive, well-funded

---

## Risk Assessment

### Technical Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| **Data quality issues** | Medium | High | Data cleanup phase before pattern recognition |
| **LLM API costs exceed budget** | Medium | Medium | Rate limiting, caching, cheaper models for simple queries |
| **Pattern recognition inaccuracy** | Medium | Medium | Confidence levels, human review initially, continuous learning |
| **Performance degradation** | Low | High | Database indexing, query optimization, caching layer |
| **Integration complexity** | Medium | Medium | Phased approach, comprehensive testing |

### Business Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| **Low user adoption** | Medium | High | Start with insights (not automation), show value early |
| **Team doesn't trust AI recommendations** | Medium | High | Transparency (show reasoning), accuracy tracking, feedback loop |
| **Scope creep during implementation** | High | Medium | Strict adherence to copilot_rules.md, resist abstraction creep |
| **Priorities change mid-development** | Medium | Medium | Modular design, complete phases before moving to next |
| **Budget exhausted before completion** | Low | High | Prioritize high-ROI features first (Phase 1), defer advanced features |

### Dependency Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| **Historical data insufficient** | Low | High | Start with foundation (Phase 1), collect data while building |
| **External API reliability** (LLM, email) | Low | Medium | Fallback mechanisms, error handling, retry logic |
| **Database performance at scale** | Low | Medium | Query optimization, indexing strategy, monitoring |
| **Team knowledge gaps** | Medium | Low | Documentation, training, code review process |

---

## Success Criteria & Validation

### Phase 1 Success Metrics

**Lead Qualification:**
- [ ] 90% of opportunities have qualification scores within 30 days
- [ ] Close rate for HOT leads (≥70) is 15% higher than COLD leads
- [ ] Sales cycle reduced by 20% for qualified opportunities

**Cost Determination:**
- [ ] 100% of opportunities have estimated cost before proposal
- [ ] Average margin increases by 8% within 6 months
- [ ] Cost estimation accuracy improves to 85% over 12 months

**Business Health KPIs:**
- [ ] Daily briefing adoption: 80% of days reviewed by GM
- [ ] Metrics dashboard used 5+ times per week
- [ ] KPI-based decisions documented in audit logs

### Phase 2 Success Metrics

**Project Costing:**
- [ ] 80% of projects track CPI throughout lifecycle
- [ ] Budget overruns detected 40% earlier
- [ ] Average project margin increases from baseline

### Phase 3 Success Metrics

**Command Center:**
- [ ] Insight action rate: 60% of high-priority insights actioned
- [ ] Decision time reduction: 40% faster for routine decisions
- [ ] Goal achievement: 85% of quarterly goals hit

### Phase 4 Success Metrics

**AI Agent:**
- [ ] Query success rate: 85% of queries answered satisfactorily
- [ ] Usage frequency: 10+ queries per week by power users
- [ ] Auto-execution accuracy: 95% of auto-actions correct

**Marketing Copilot:**
- [ ] 50% increase in monthly lead volume within 3 months
- [ ] Cost per lead reduced by 30%
- [ ] Marketing-sourced revenue >40% of total

---

## Budget Estimation

### Development Costs

| Phase | Hours | Rate ($75/hr) | Total |
|-------|-------|---------------|-------|
| Phase 1: Foundation | 264 | $75 | $19,800 |
| Phase 2: Project Costing | 110 | $75 | $8,250 |
| Phase 3: Command Center | 184 | $75 | $13,800 |
| Phase 4A: AI Agent | 196 | $75 | $14,700 |
| Phase 4B: Marketing | 206 | $75 | $15,450 |
| **TOTAL** | **960** | **$75** | **$72,000** |

### Ongoing Costs (Monthly)

| Service | Cost | Notes |
|---------|------|-------|
| LLM API (AI Agent) | $500-$2000 | Based on query volume |
| Email Service (Marketing) | $50-$200 | Mailgun/SendGrid |
| Database Hosting | $0 | Already covered |
| Monitoring/Logging | $0 | Already covered |
| **TOTAL** | **$550-$2200** | **If Phase 4 implemented** |

---

## Next Actions

### Immediate Steps (Before Implementation)

1. **Review & Approve**
   - [ ] Review this task breakdown with stakeholders
   - [ ] Approve Phase 1 budget ($19,800)
   - [ ] Prioritize features (MVP vs future)
   - [ ] Set success metrics and KPIs

2. **Team Preparation**
   - [ ] Assign resources (1-3 developers)
   - [ ] Review copilot_rules.md with team
   - [ ] Set up development environment
   - [ ] Create project tracking board

3. **Data Preparation**
   - [ ] Audit existing opportunity data quality
   - [ ] Identify data gaps
   - [ ] Plan data cleanup if needed
   - [ ] Establish data quality standards

4. **Architecture Validation**
   - [ ] Review proposed schemas with DBA
   - [ ] Validate service patterns with team
   - [ ] Plan testing approach
   - [ ] Set code review process

### Decision Points

**GO / NO-GO Checklist:**

- [ ] **Budget approved:** Phase 1 ($19,800) minimum
- [ ] **Resources assigned:** At least 1 developer available for 6-8 weeks
- [ ] **Data quality acceptable:** Existing opportunity data sufficient
- [ ] **Priorities aligned:** Team agrees Phase 1 is highest value
- [ ] **Architecture approved:** Patterns follow copilot_rules.md
- [ ] **Success metrics defined:** Clear KPIs for Phase 1
- [ ] **Stakeholder buy-in:** Leadership committed to using new features

**If ANY NO:**
- Defer implementation until resolved
- Start with subset (e.g., Lead Qualification only)
- Reconsider priorities

---

## Conclusion

This task breakdown provides a **complete, actionable roadmap** to transform OPF-CD from a data management system into an intelligent operations platform over 7-9 months with 2-3 developers.

### Key Recommendations

1. **Start with Phase 1** (Lead Qualification, Cost Determination, Business KPIs)
   - Highest ROI
   - No dependencies
   - Immediate business value
   - 6-8 weeks, $19,800

2. **Defer Phase 4** (AI Agent, Marketing) until Phases 1-3 complete
   - Advanced features
   - Require complete business context
   - Higher complexity
   - Consider market demand first

3. **Follow Copilot Rules Strictly**
   - Every task references copilot_rules.md compliance
   - Single service injection
   - No calculations in controllers
   - No frontend logic

4. **Test Continuously**
   - Every section includes testing tasks
   - Integration tests following Phase 5.4.5 patterns
   - Code coverage >80% target

5. **Measure Success**
   - Clear KPIs for each phase
   - Track adoption metrics
   - Validate business impact
   - Iterate based on feedback

---

**Document Version:** 1.0  
**Generated:** February 27, 2026  
**Author:** GitHub Copilot (Claude Sonnet 4.5)  
**Source:** STRATEGIC_VISION_INTELLIGENT_OPERATIONS.md  
**Next Review:** After Phase 1 completion

**Revision History:**
- v1.0 (Feb 27, 2026): Initial comprehensive task breakdown

---

## Quick Reference: Task Summary

| Section | Tasks | Effort (hrs) | Priority | Status |
|---------|-------|--------------|----------|--------|
| 1. Business Health KPIs | 9 | 108 | HIGH | ❌ Not Started |
| 2. Lead Qualification | 5 | 72 | HIGH | ❌ Not Started |
| 3. Cost Determination | 6 | 84 | HIGH | ❌ Not Started |
| 4. Project Templates | - | - | - | ✅ COMPLETE (Phase 5.4) |
| 5. Project Costing EVM | 7 | 110 | MEDIUM | ❌ Not Started |
| 6. Command Center | 10 | 184 | MEDIUM | ❌ Not Started |
| 7. AI Agent | 10 | 196 | LOW | ❌ Not Started |
| 8. Marketing Copilot | 12 | 206 | LOW | ❌ Not Started |
| **TOTAL** | **59** | **960** | - | **11% Complete** |

**Critical Path:** Tasks 1-3 → Task 5 → Task 6 → Task 7  
**Parallel Path:** Task 8 can start after Task 2 completes

---

**END OF DOCUMENT**
