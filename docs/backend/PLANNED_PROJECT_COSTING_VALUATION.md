# Project Costing Valuation Feature (Planned)

**Status:** PLANNED - Not Yet Implemented  
**Priority:** Phase 4  
**Estimated Effort:** 2-3 weeks  
**Dependencies:** Current EVM (Earned Value Management) foundation via PaymentGapService

---

## Executive Summary

Extend the current Payment Gap analytics to include **comprehensive cost tracking and forecasting** using Earned Value Management principles. This feature will enable accurate project profitability analysis, budget variance tracking, and cost-to-completion predictions.

**Current State:**
- ✅ We track **Earned Value** (contract_value × progress)
- ✅ We track **Payment Gap** (earned - received)
- ❌ We DON'T track **Actual Costs** (expenses, labor, overhead)
- ❌ We DON'T track **Planned/Budgeted Costs** per task/milestone

**Desired State:**
- Track budgeted costs per task and milestone
- Calculate actual costs from expenses and labor
- Compute cost performance metrics (CPI, SPI, EAC)
- Predict final project costs and profitability

---

## Feature Requirements

### 1. Budget Tracking (Planned Costs)

**Goal:** Define expected costs for each task and milestone before work begins.

#### Schema Changes Required:

```sql
-- Add budget columns to tasks table
ALTER TABLE tasks
ADD COLUMN budgeted_cost NUMERIC(15,2) DEFAULT 0 CHECK (budgeted_cost >= 0);

-- Add budget columns to payment_milestones table
ALTER TABLE payment_milestones
ADD COLUMN budgeted_cost NUMERIC(15,2) DEFAULT 0 CHECK (budgeted_cost >= 0);

-- Add project-level budget tracking
ALTER TABLE projects
ADD COLUMN total_budgeted_cost NUMERIC(15,2) DEFAULT 0 CHECK (total_budgeted_cost >= 0);

COMMENT ON COLUMN tasks.budgeted_cost IS 'Planned/budgeted cost to complete this task (excludes profit margin)';
COMMENT ON COLUMN payment_milestones.budgeted_cost IS 'Planned cost associated with this milestone deliverable';
COMMENT ON COLUMN projects.total_budgeted_cost IS 'Total planned cost for entire project (sum of all task budgets)';
```

#### Business Rules:
- **Budgeted cost ≠ Contract value** (cost is internal, contract is external)
- **Profit margin** = Contract Value - Total Budgeted Cost
- Budgets set during project planning phase
- Can be revised but with audit trail
- Task budget sum should approximately equal project total budget

---

### 2. Actual Cost Tracking

**Goal:** Aggregate all real costs incurred during project execution.

#### Cost Sources:
1. **Direct Expenses** (from expenses table where project_id is set)
2. **Labor Costs** (requires new labor tracking - see Phase 5)
3. **Overhead Allocation** (optional - % of indirect costs)

#### New Service: `ProjectCostService.php`

```php
class ProjectCostService
{
    /**
     * Calculate actual costs incurred for a project
     * 
     * Sources:
     * - Direct expenses (posted expenses linked to project)
     * - Labor costs (hours × rate - future feature)
     * - Allocated overhead (optional)
     * 
     * @param int $projectId
     * @return array ['actual_cost' => float, 'by_category' => array]
     */
    public function getActualCosts(int $projectId): array
    {
        // Get posted expenses for this project
        $expenseCost = DB::table('expenses')
            ->where('project_id', $projectId)
            ->where('status', 'posted')
            ->sum('amount');
        
        // TODO: Add labor costs when labor tracking implemented
        $laborCost = 0;
        
        // TODO: Add overhead allocation
        $overheadCost = 0;
        
        return [
            'actual_cost' => $expenseCost + $laborCost + $overheadCost,
            'by_category' => [
                'expenses' => $expenseCost,
                'labor' => $laborCost,
                'overhead' => $overheadCost
            ]
        ];
    }
}
```

---

### 3. Earned Value Management Metrics

**Goal:** Calculate standard EVM indices and forecasts.

#### Key Metrics:

| Metric | Formula | Interpretation |
|--------|---------|----------------|
| **Planned Value (PV)** | Budget × Scheduled Progress | What we planned to complete by now |
| **Earned Value (EV)** | Budget × Actual Progress | Value of work actually completed |
| **Actual Cost (AC)** | Sum of all costs incurred | What we actually spent |
| **Cost Variance (CV)** | EV - AC | Positive = under budget, Negative = over budget |
| **Schedule Variance (SV)** | EV - PV | Positive = ahead, Negative = behind |
| **Cost Performance Index (CPI)** | EV / AC | > 1.0 = efficient, < 1.0 = over budget |
| **Schedule Performance Index (SPI)** | EV / PV | > 1.0 = ahead, < 1.0 = behind |
| **Estimate at Completion (EAC)** | Budget / CPI | Predicted final cost |
| **Estimate to Complete (ETC)** | EAC - AC | How much more money needed |
| **Variance at Completion (VAC)** | Budget - EAC | Final expected profit/loss |

#### New Service: `EarnedValueService.php`

```php
class EarnedValueService
{
    private ProjectCostService $costService;
    private ProjectProgressService $progressService;

    /**
     * Calculate full EVM analysis for a project
     * 
     * @param int $projectId
     * @return array [
     *   'pv' => float,  // Planned Value
     *   'ev' => float,  // Earned Value
     *   'ac' => float,  // Actual Cost
     *   'cv' => float,  // Cost Variance
     *   'sv' => float,  // Schedule Variance
     *   'cpi' => float, // Cost Performance Index
     *   'spi' => float, // Schedule Performance Index
     *   'eac' => float, // Estimate at Completion
     *   'etc' => float, // Estimate to Complete
     *   'vac' => float, // Variance at Completion
     *   'health' => string, // 'good', 'warning', 'critical'
     * ]
     */
    public function getEVMAnalysis(int $projectId): array
    {
        $project = DB::table('projects')->where('id', $projectId)->first();
        
        // Get actual progress
        $actualProgress = $this->progressService->calculateProjectProgress($projectId);
        
        // Get actual costs
        $costs = $this->costService->getActualCosts($projectId);
        $actualCost = $costs['actual_cost'];
        
        // Calculate metrics
        $budget = $project->total_budgeted_cost;
        $earnedValue = $budget * ($actualProgress / 100);
        
        // For PV, we'd need planned schedule (future enhancement)
        // For now, assume linear: planned progress = (days elapsed / total days) × 100
        $plannedProgress = $this->calculatePlannedProgress($project);
        $plannedValue = $budget * ($plannedProgress / 100);
        
        $costVariance = $earnedValue - $actualCost;
        $scheduleVariance = $earnedValue - $plannedValue;
        
        $cpi = $actualCost > 0 ? $earnedValue / $actualCost : 0;
        $spi = $plannedValue > 0 ? $earnedValue / $plannedValue : 0;
        
        $eac = $cpi > 0 ? $budget / $cpi : $budget;
        $etc = $eac - $actualCost;
        $vac = $budget - $eac;
        
        return [
            'pv' => round($plannedValue, 2),
            'ev' => round($earnedValue, 2),
            'ac' => round($actualCost, 2),
            'cv' => round($costVariance, 2),
            'sv' => round($scheduleVariance, 2),
            'cpi' => round($cpi, 2),
            'spi' => round($spi, 2),
            'eac' => round($eac, 2),
            'etc' => round($etc, 2),
            'vac' => round($vac, 2),
            'health' => $this->determineHealth($cpi, $spi),
            'interpretation' => [
                'cost_status' => $cpi >= 1.0 ? 'under budget' : 'over budget',
                'schedule_status' => $spi >= 1.0 ? 'ahead of schedule' : 'behind schedule',
                'projected_profit' => $project->contract_value - $eac
            ]
        ];
    }
    
    private function determineHealth(float $cpi, float $spi): string
    {
        if ($cpi >= 0.95 && $spi >= 0.95) return 'good';
        if ($cpi >= 0.80 && $spi >= 0.80) return 'warning';
        return 'critical';
    }
}
```

---

### 4. Variance Analysis Dashboard

**Goal:** Provide visual comparisons of planned vs actual performance.

#### UI Components:

**Budget vs Actual Table:**
```
┌──────────────────┬──────────┬────────────┬──────────┬──────────┐
│ Task             │ Budget   │ Actual     │ Variance │ Status   │
├──────────────────┼──────────┼────────────┼──────────┼──────────┤
│ Design           │ 100,000  │ 95,000     │ +5,000   │ ✓ Good   │
│ Development      │ 500,000  │ 550,000    │ -50,000  │ ⚠ Over   │
│ Testing          │ 100,000  │ 0          │ 0        │ - Pending│
│ Deployment       │ 50,000   │ 0          │ 0        │ - Pending│
├──────────────────┼──────────┼────────────┼──────────┼──────────┤
│ TOTAL            │ 750,000  │ 645,000    │ +105,000 │ ✓ Good   │
└──────────────────┴──────────┴────────────┴──────────┴──────────┘
```

**EVM Trend Chart:**
- X-axis: Time (project timeline)
- Y-axis: Value (currency)
- Lines: Planned Value (PV), Earned Value (EV), Actual Cost (AC)
- Shows divergence over time

**Cost Performance Card:**
```
┌─────────────────────────────────────┐
│ Cost Performance Index (CPI)        │
│                                     │
│         1.16                        │
│     ▲ 16% under budget              │
│                                     │
│ You're spending efficiently!        │
└─────────────────────────────────────┘
```

**Forecast Card:**
```
┌─────────────────────────────────────┐
│ Estimate at Completion (EAC)        │
│                                     │
│     UGX 645,000 / 750,000           │
│     Expected Profit: +355,000       │
│                                     │
│ Project tracking to beat budget     │
└─────────────────────────────────────┘
```

---

## Implementation Roadmap

### Phase 1: Database Schema (Week 1)
- [ ] Add budget columns to tasks, milestones, projects
- [ ] Create migration scripts
- [ ] Update seed data with sample budgets
- [ ] Add database constraints and comments

### Phase 2: Cost Tracking Services (Week 1-2)
- [ ] Create `ProjectCostService` for actual cost aggregation
- [ ] Update `ExpenseManagementService` to link expenses to projects
- [ ] Add cost tracking to task updates
- [ ] Write unit tests for cost calculations

### Phase 3: EVM Calculations (Week 2)
- [ ] Create `EarnedValueService` with all metrics
- [ ] Implement CPI, SPI, EAC calculations
- [ ] Add planned progress tracking (schedule baseline)
- [ ] Write comprehensive tests for EVM formulas

### Phase 4: API Endpoints (Week 2-3)
- [ ] `GET /api/projects/{id}/costs` - Actual costs breakdown
- [ ] `GET /api/projects/{id}/evm` - Full EVM analysis
- [ ] `GET /api/projects/{id}/variance` - Variance report
- [ ] Add to existing project analytics endpoint

### Phase 5: UI Integration (Week 3)
- [ ] Budget input forms (task creation/editing)
- [ ] Variance analysis dashboard
- [ ] EVM trend charts
- [ ] Cost performance cards

### Phase 6: Testing & Validation (Week 3)
- [ ] Unit tests for all services
- [ ] Integration tests with real data
- [ ] Manual QA testing
- [ ] Performance optimization

---

## Data Requirements

### Minimum Viable Data:
1. **Task Budgets** - Set during project planning
2. **Project Budget** - Sum of all task budgets
3. **Expense-to-Project Links** - Already exists in schema
4. **Task Progress** - Already tracked

### Future Enhancements:
1. **Labor Tracking** - Hours worked per task × hourly rate
2. **Schedule Baseline** - Planned completion dates per task
3. **Budget Revisions** - Audit trail of budget changes
4. **Multi-Currency Budgets** - If projects span currencies

---

## Business Value

### For Project Managers:
- **Early Warning System** - Detect cost overruns before completion
- **Resource Optimization** - See where money is being spent efficiently
- **Accurate Forecasting** - Predict final costs with confidence

### For Finance Team:
- **Profitability Analysis** - Compare revenue (contract) vs costs
- **Cash Flow Planning** - Predict when expenses will occur
- **Budget Accountability** - Track variance against approved budgets

### For Executive Leadership:
- **Portfolio View** - See which projects are profitable
- **Strategic Decisions** - Data-driven resource allocation
- **Risk Management** - Identify troubled projects early

---

## Example Scenario

**Project:** CRM Website Redesign  
**Contract Value:** UGX 1,000,000  
**Total Budget:** UGX 750,000 (75% margin expectation)  
**Expected Profit:** UGX 250,000 (25%)

**After 85% Progress:**
- **Earned Value:** UGX 637,500 (750,000 × 0.85)
- **Actual Cost:** UGX 550,000 (expenses incurred)
- **Cost Variance:** +87,500 (under budget!)
- **CPI:** 1.16 (spending efficiently)
- **EAC:** UGX 646,551 (projected final cost)
- **Projected Profit:** UGX 353,449 (35% margin!)

**Interpretation:**
> "You're 85% done and have spent UGX 550K of your 750K budget. At this rate, you'll finish UGX 103K under budget, increasing profit margin from 25% to 35%."

---

## Dependencies

### Required Before Implementation:
1. ✅ Task weight validation (already implemented)
2. ✅ Status/progress synchronization (just implemented)
3. ✅ Expense tracking with project links (exists)
4. ✅ Project progress calculation (PaymentGapService)

### Future Requirements (Phase 5+):
1. ⏳ Labor/time tracking system
2. ⏳ Schedule baseline (planned dates per task)
3. ⏳ Budget revision workflow with approvals
4. ⏳ Multi-project portfolio analytics

---

## Technical Notes

### Performance Considerations:
- Cache EVM calculations (expensive aggregations)
- Index expenses.project_id for fast filtering
- Pre-calculate project totals on task/expense updates
- Use materialized views for historical trends

### Data Integrity:
- Ensure expenses have valid project_id
- Validate budget sum matches project total
- Prevent negative budgets/costs
- Audit trail for budget changes

### Security:
- Budget data is financially sensitive
- Restrict access to finance role
- Log all budget modifications
- Encrypt cost data at rest (if required)

---

## Success Metrics

After implementation, measure:
1. **Adoption Rate** - % of projects with budgets set
2. **Forecast Accuracy** - Compare EAC predictions to actual final costs
3. **Decision Impact** - Projects rescued/cancelled based on EVM data
4. **Time Saved** - Hours saved vs manual Excel tracking

---

## References

- **PMBOK Guide** (Project Management Body of Knowledge) - EVM chapter
- **Current Implementation:** `PaymentGapService.php` (foundation)
- **Related Services:** `ProjectProgressService.php`, `ExpenseManagementService.php`
- **Schema:** `tasks`, `expenses`, `projects` tables

---

## Next Steps (When Ready to Implement)

1. Review this plan with stakeholders
2. Refine budget categories and cost breakdown structure
3. Design UI mockups for variance dashboard
4. Create database migration scripts
5. Begin Phase 1 implementation

**Estimated Start Date:** TBD  
**Estimated Completion:** TBD  
**Priority:** Medium (after Phase 3 completion)
