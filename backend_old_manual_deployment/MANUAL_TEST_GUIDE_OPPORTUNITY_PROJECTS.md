# Manual Test Guide: Opportunity to Project Conversion

## Overview
This guide covers manual testing of the multi-phase opportunity project creation feature, including:
- Automatic project creation when opportunity is won
- Duplicate prevention on stage transitions
- Manual project creation for multi-phase opportunities
- Project independence from opportunity stage changes

---

## Prerequisites

1. **Authentication Required**: All endpoints require authentication via session
2. **Permissions Required**: 
   - `opportunities.view` - View opportunities and their projects
   - `opportunities.edit` - Update opportunities and create projects
3. **Base URL**: `http://your-domain.com/api`

---

## Test Scenario 1: Auto-Creation on First "Won"

### Step 1.1: Create a New Opportunity
**Endpoint**: `POST /api/opportunities`

**Request Body**:
```json
{
  "client": "Acme Corporation",
  "description": "Enterprise software implementation project",
  "stage": "qualified",
  "estimated_value": 250000,
  "probability": 60,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-06-30",
  "owner": 1
}
```

**Expected Response** (201):
```json
{
  "success": true,
  "message": "Opportunity created successfully.",
  "opportunity_id": 14
}
```

**Save**: Note the `opportunity_id` (e.g., 14)

---

### Step 1.2: Update Opportunity to "Won"
**Endpoint**: `PUT /api/opportunities/{opportunityId}`
**Replace** `{opportunityId}` with the ID from Step 1.1 (e.g., 14)

**Request Body**:
```json
{
  "client": "Acme Corporation",
  "description": "Enterprise software implementation project",
  "stage": "won",
  "estimated_value": 250000,
  "probability": 100,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-06-30",
  "owner": 1
}
```

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Opportunity updated successfully. Project created automatically (ID: 14).",
  "project_created": true,
  "project_id": 14,
  "existing_projects": []
}
```

**Verify**:
- `project_created` is `true`
- `project_id` is present
- Auto-created project has:
  - Name: "Acme Corporation - Project (2026-02-20 HH:MM)"
  - Client: "Acme Corporation"
  - Contract Value: 250000.00
  - Contract Currency: "USD"
  - Status: "planned"
  - Start Date: Current date
  - End Date: NULL
  - Project Lead: NULL
  - Opportunity ID: 14

---

## Test Scenario 2: Duplicate Prevention

### Step 2.1: Change Won Opportunity to Different Stage
**Endpoint**: `PUT /api/opportunities/{opportunityId}`

**Request Body**:
```json
{
  "client": "Acme Corporation",
  "description": "Enterprise software implementation project",
  "stage": "negotiation",
  "estimated_value": 250000,
  "probability": 80,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-06-30",
  "owner": 1
}
```

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Opportunity updated successfully.",
  "project_created": false,
  "project_id": null,
  "existing_projects": []
}
```

**Verify**: Existing project (ID: 14) still exists and is unchanged

---

### Step 2.2: Change Back to "Won"
**Endpoint**: `PUT /api/opportunities/{opportunityId}`

**Request Body**:
```json
{
  "client": "Acme Corporation",
  "description": "Enterprise software implementation project",
  "stage": "won",
  "estimated_value": 250000,
  "probability": 100,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-06-30",
  "owner": 1
}
```

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Opportunity updated successfully. Opportunity marked as won. 1 existing project(s) found (IDs: 14).",
  "project_created": false,
  "project_id": null,
  "existing_projects": [14]
}
```

**Verify**:
- `project_created` is `false`
- `project_id` is `null`
- `existing_projects` contains the original project ID
- NO duplicate project was created
- Message indicates existing project found

---

## Test Scenario 3: Manual Project Creation (Phase 2)

### Step 3.1: Create Second Project (Phase 2)
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body**:
```json
{
  "name": "Acme Corporation - Phase 2: Mobile App Development",
  "contract_value": 150000,
  "contract_currency": "USD",
  "start_date": "2026-07-01",
  "end_date": "2026-12-31",
  "status": "planned",
  "project_lead_id": null
}
```

**Expected Response** (201):
```json
{
  "success": true,
  "project_id": 15,
  "message": "Project created successfully and linked to opportunity"
}
```

**Verify**:
- Project created with user-provided name (not auto-generated)
- Client copied from opportunity ("Acme Corporation")
- Contract value is user-provided (150000, not 250000)
- Project linked to same opportunity (opportunity_id: 14)

---

### Step 3.2: Create Third Project (Phase 3 - Ongoing)
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body**:
```json
{
  "name": "Acme Corporation - Phase 3: Maintenance & Support",
  "contract_value": 5000,
  "contract_currency": "USD",
  "start_date": "2027-01-01",
  "end_date": null,
  "status": "planned",
  "project_lead_id": null
}
```

**Expected Response** (201):
```json
{
  "success": true,
  "project_id": 16,
  "message": "Project created successfully and linked to opportunity"
}
```

**Verify**: `end_date` is null (ongoing maintenance contract)

---

## Test Scenario 4: List Projects for Opportunity

### Step 4.1: Get All Projects Linked to Opportunity
**Endpoint**: `GET /api/opportunities/{opportunityId}/projects`

**Expected Response** (200):
```json
{
  "success": true,
  "projects": [
    {
      "id": 14,
      "name": "Acme Corporation - Project (2026-02-20 11:38)",
      "client": "Acme Corporation",
      "contract_value": "250000.00",
      "contract_currency": "USD",
      "start_date": "2026-02-20",
      "end_date": null,
      "status": "planned",
      "project_lead_id": null,
      "opportunity_id": 14,
      "created_at": "2026-02-20T11:38:15.000000Z",
      "updated_at": "2026-02-20T11:38:15.000000Z"
    },
    {
      "id": 15,
      "name": "Acme Corporation - Phase 2: Mobile App Development",
      "client": "Acme Corporation",
      "contract_value": "150000.00",
      "contract_currency": "USD",
      "start_date": "2026-07-01",
      "end_date": "2026-12-31",
      "status": "planned",
      "project_lead_id": null,
      "opportunity_id": 14,
      "created_at": "2026-02-20T11:45:22.000000Z",
      "updated_at": "2026-02-20T11:45:22.000000Z"
    },
    {
      "id": 16,
      "name": "Acme Corporation - Phase 3: Maintenance & Support",
      "client": "Acme Corporation",
      "contract_value": "5000.00",
      "contract_currency": "USD",
      "start_date": "2027-01-01",
      "end_date": null,
      "status": "planned",
      "project_lead_id": null,
      "opportunity_id": 14,
      "created_at": "2026-02-20T11:46:05.000000Z",
      "updated_at": "2026-02-20T11:46:05.000000Z"
    }
  ],
  "count": 3
}
```

**Verify**:
- All 3 projects returned
- All have same `client` ("Acme Corporation")
- All have same `opportunity_id` (14)
- Projects ordered by creation date (newest last)

---

## Test Scenario 5: Project Independence

### Step 5.1: Mark Opportunity as "Lost"
**Endpoint**: `PUT /api/opportunities/{opportunityId}`

**Request Body**:
```json
{
  "client": "Acme Corporation",
  "description": "Enterprise software implementation project - CANCELLED",
  "stage": "lost",
  "estimated_value": 250000,
  "probability": 0,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-06-30",
  "owner": 1
}
```

**Expected Response** (200):
```json
{
  "success": true,
  "message": "Opportunity updated successfully.",
  "project_created": false,
  "project_id": null,
  "existing_projects": []
}
```

---

### Step 5.2: Verify Projects Still Exist
**Endpoint**: `GET /api/opportunities/{opportunityId}/projects`

**Expected Response** (200):
```json
{
  "success": true,
  "projects": [
    {...},
    {...},
    {...}
  ],
  "count": 3
}
```

**Verify**:
- All 3 projects still exist
- Projects are NOT cancelled or deleted
- Projects maintain their original status ("planned")
- Projects have independent lifecycle from opportunity

---

## Test Scenario 6: Validation Tests

### Step 6.1: Missing Required Field
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body** (missing `name`):
```json
{
  "contract_value": 50000,
  "contract_currency": "USD",
  "start_date": "2026-03-01"
}
```

**Expected Response** (422):
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": [
      "Project name is required"
    ]
  }
}
```

---

### Step 6.2: Invalid End Date (Before Start Date)
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body**:
```json
{
  "name": "Invalid Project",
  "contract_value": 50000,
  "contract_currency": "USD",
  "start_date": "2026-06-01",
  "end_date": "2026-05-01"
}
```

**Expected Response** (422):
```json
{
  "message": "The end date field must be a date after or equal to start date.",
  "errors": {
    "end_date": [
      "End date must be on or after start date"
    ]
  }
}
```

---

### Step 6.3: Invalid Currency
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body**:
```json
{
  "name": "Invalid Currency Project",
  "contract_value": 50000,
  "contract_currency": "EUR",
  "start_date": "2026-03-01"
}
```

**Expected Response** (422):
```json
{
  "message": "The selected contract currency is invalid.",
  "errors": {
    "contract_currency": [
      "Currency must be USD or UGX"
    ]
  }
}
```

---

### Step 6.4: Negative Contract Value
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body**:
```json
{
  "name": "Negative Value Project",
  "contract_value": -10000,
  "contract_currency": "USD",
  "start_date": "2026-03-01"
}
```

**Expected Response** (422):
```json
{
  "message": "The contract value field must be at least 0.",
  "errors": {
    "contract_value": [
      "Contract value must be positive"
    ]
  }
}
```

---

## Test Scenario 7: Edge Cases

### Step 7.1: Create Project for Non-Won Opportunity
**Setup**: Create opportunity with stage "qualified" (not "won")

**Endpoint**: `POST /api/opportunities/{newOpportunityId}/projects`

**Request Body**:
```json
{
  "name": "Early Phase Project",
  "contract_value": 100000,
  "contract_currency": "USD",
  "start_date": "2026-03-01",
  "end_date": "2026-09-01"
}
```

**Expected Response** (201):
```json
{
  "success": true,
  "project_id": 17,
  "message": "Project created successfully and linked to opportunity"
}
```

**Verify**: Manual creation works regardless of opportunity stage

---

### Step 7.2: Create Project with All Optional Fields
**Endpoint**: `POST /api/opportunities/{opportunityId}/projects`

**Request Body** (minimal - only required fields):
```json
{
  "name": "Minimal Project",
  "contract_value": 25000,
  "contract_currency": "UGX",
  "start_date": "2026-04-01"
}
```

**Expected Response** (201):
```json
{
  "success": true,
  "project_id": 18,
  "message": "Project created successfully and linked to opportunity"
}
```

**Verify**:
- `end_date`: null
- `status`: "planned" (default)
- `project_lead_id`: null
- `client`: Copied from opportunity

---

## Sample Test Data Sets

### Data Set 1: Small Business
```json
{
  "client": "Local Bakery Co.",
  "description": "Point of sale system implementation",
  "stage": "proposal",
  "estimated_value": 15000,
  "probability": 70,
  "currency": "UGX",
  "source": "cold call",
  "expected_close_date": "2026-03-15",
  "owner": 1
}
```

### Data Set 2: Enterprise Client
```json
{
  "client": "Global Industries Inc.",
  "description": "ERP system migration and integration",
  "stage": "negotiation",
  "estimated_value": 1500000,
  "probability": 85,
  "currency": "USD",
  "source": "referral",
  "expected_close_date": "2026-12-31",
  "owner": 1
}
```

### Data Set 3: Government Contract
```json
{
  "client": "Ministry of Health",
  "description": "Health records digitization platform",
  "stage": "qualified",
  "estimated_value": 5000000,
  "probability": 50,
  "currency": "UGX",
  "source": "tender",
  "expected_close_date": "2026-09-30",
  "owner": 1
}
```

---

## Testing Checklist

### ✅ Automatic Project Creation
- [ ] First "won" stage change creates project
- [ ] Auto-created project has correct fields
- [ ] Auto-created project links to opportunity

### ✅ Duplicate Prevention
- [ ] won → other → won does NOT create duplicate
- [ ] Message indicates existing projects found
- [ ] `existing_projects` array contains IDs

### ✅ Manual Project Creation
- [ ] Can create multiple projects per opportunity
- [ ] User-provided fields used (name, value, dates)
- [ ] Client always copied from opportunity
- [ ] Works regardless of opportunity stage

### ✅ Project Listing
- [ ] GET endpoint returns all projects
- [ ] Projects ordered by creation date
- [ ] Count matches actual number

### ✅ Project Independence
- [ ] Opportunity stage changes don't affect projects
- [ ] Projects not deleted when opportunity lost
- [ ] Projects maintain original status

### ✅ Validation
- [ ] Required fields enforced
- [ ] Date validation works (end >= start)
- [ ] Currency validation (USD/UGX only)
- [ ] Contract value must be non-negative

### ✅ Permissions
- [ ] Requires `opportunities.view` for GET
- [ ] Requires `opportunities.edit` for POST
- [ ] 403 returned without permission

---

## Expected Database State After Full Test

**opportunities table**:
```
id | client              | stage | estimated_value | currency
14 | Acme Corporation    | lost  | 250000.00       | USD
```

**projects table**:
```
id | name                                                    | contract_value | opportunity_id | status
14 | Acme Corporation - Project (2026-02-20 11:38)          | 250000.00      | 14             | planned
15 | Acme Corporation - Phase 2: Mobile App Development     | 150000.00      | 14             | planned
16 | Acme Corporation - Phase 3: Maintenance & Support      | 5000.00        | 14             | planned
```

**Relationship**: 1 opportunity → 3 projects (1:many)

---

## Troubleshooting

### Issue: "Opportunity not found"
**Cause**: Invalid opportunity ID  
**Solution**: Verify opportunity exists using `GET /api/opportunities/{id}`

### Issue: 403 Forbidden
**Cause**: Missing permissions  
**Solution**: Ensure user has `opportunities.edit` permission

### Issue: Auto-creation not working
**Cause**: Opportunity updated via direct DB query instead of API  
**Solution**: Always use `PUT /api/opportunities/{id}` endpoint

### Issue: Client field validation error
**Cause**: Attempting to send `client` in manual creation request  
**Solution**: Remove `client` field - it's automatically copied from opportunity

---

## Success Criteria

✅ **All tests pass**  
✅ **No duplicate projects created on stage transitions**  
✅ **Multiple projects can be manually created**  
✅ **Projects remain independent of opportunity changes**  
✅ **All validations working correctly**  
✅ **Audit trail captured for all operations**
