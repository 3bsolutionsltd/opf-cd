# Quick Guide: Testing Multi-Phase Opportunity Projects

## 🎯 How to Test Manual Project Creation for Phased Opportunities

### Method 1: Using the UI (Recommended for Users)

#### Step 1: Navigate to Opportunity Projects
1. Go to **Opportunities** page: `http://your-domain.com/opportunities`
2. Find the opportunity you want to work with
3. Click the **"Projects"** button in the Actions column

#### Step 2: Create a New Project Phase
1. On the projects page, click **"Create Project (Phase)"** button
2. Fill in the project details:
   - **Project Name**: e.g., "Phase 1: Mobile App Development"
   - **Contract Value**: Numeric value (e.g., 150000)
   - **Currency**: USD or UGX (defaults to opportunity currency)
   - **Start Date**: Required (e.g., 2026-03-01)
   - **End Date**: Optional (leave blank for ongoing projects)
   - **Status**: planned, active, on_hold, completed, or cancelled

3. Click **"Create Project"**
4. View the newly created project in the list

#### Step 3: Create Additional Phases
- Repeat Step 2 to create Phase 2, Phase 3, etc.
- Each project is linked to the same opportunity
- All projects display with their phase names

### Method 2: Using API (For Testing/Automation)

#### Create Project via API
```bash
POST /api/opportunities/{opportunityId}/projects
```

**Headers:**
```json
{
  "Content-Type": "application/json",
  "X-CSRF-TOKEN": "your-csrf-token"
}
```

**Request Body:**
```json
{
  "name": "Phase 2: API Integration",
  "contract_value": 75000,
  "contract_currency": "USD",
  "start_date": "2026-07-01",
  "end_date": "2026-12-31",
  "status": "planned"
}
```

**Response:**
```json
{
  "success": true,
  "project_id": 15,
  "message": "Project created successfully and linked to opportunity"
}
```

---

## 📋 How to View All Projects for a Specific Opportunity

### Option 1: UI - Projects Page (New!)

1. **Navigate to Opportunities**: `http://your-domain.com/opportunities`
2. **Click "Projects"** button for the desired opportunity
3. **View all linked projects** in a dedicated page showing:
   - Phase/Project name
   - Contract value
   - Status (planned, active, on_hold, completed, cancelled)
   - Start and end dates
   - Project lead assignment status
   - Creation date
   - Opportunity details at the top

### Option 2: API Endpoint

```bash
GET /api/opportunities/{opportunityId}/projects
```

**Headers:**
```json
{
  "Accept": "application/json"
}
```

**Response:**
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
      "name": "Acme Corporation - Phase 2: Mobile Development",
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
    }
  ],
  "count": 2
}
```

---

## 🔄 Complete Workflow Example

### Scenario: Multi-Phase Software Implementation

#### Phase 1: Auto-Created Project (When Opportunity Won)
1. Create opportunity: "Enterprise Software Implementation"
   - Client: "TechCorp Inc."
   - Estimated Value: $500,000
   - Stage: qualified

2. Update opportunity stage to **"won"**
   - System automatically creates **Phase 1 project**:
     - Name: "TechCorp Inc. - Project (2026-02-20 14:30)"
     - Contract Value: $500,000 (from opportunity)
     - Status: planned

#### Phase 2: Manual Creation (Additional Development)
1. Click **"Projects"** button on opportunity
2. Click **"Create Project (Phase)"**
3. Enter details:
   - Name: "TechCorp Inc. - Phase 2: Mobile App"
   - Contract Value: $200,000
   - Start Date: 2026-07-01
   - End Date: 2026-12-31
   - Status: planned

#### Phase 3: Manual Creation (Ongoing Maintenance)
1. Click **"Create Project (Phase)"** again
2. Enter details:
   - Name: "TechCorp Inc. - Phase 3: Maintenance & Support"
   - Contract Value: $10,000/month
   - Start Date: 2027-01-01
   - End Date: (leave blank for ongoing)
   - Status: planned

### Result
You now have **3 projects** linked to one opportunity:
- Phase 1: Core implementation ($500k) - Auto-created
- Phase 2: Mobile app ($200k) - Manually created
- Phase 3: Ongoing support ($10k/month) - Manually created

All viewable on the **Projects** page for this opportunity.

---

## 🎨 UI Features

### Projects Page Includes:
✅ **Opportunity Card** - Shows client, value, stage, total projects  
✅ **Projects Table** - Lists all phases with details  
✅ **Create Modal** - Form to add new project phases  
✅ **Empty State** - Helpful message when no projects exist  
✅ **Info Note** - Explains multi-phase functionality  
✅ **Auto-refresh** - List updates after creating projects  
✅ **Currency Defaulting** - Uses opportunity currency by default  

### Navigation:
- **From Opportunities List** → Click "Projects" button
- **From Projects Page** → "Back to Opportunities" button
- **Direct URL**: `/opportunities/{id}/projects`

---

## 🛠 Testing Checklist

### Manual Creation Tests:
- [ ] Create project when opportunity is "won"
- [ ] Create project when opportunity is "qualified" (not won)
- [ ] Create multiple projects (3+) for same opportunity
- [ ] Create project with end date
- [ ] Create project without end date (ongoing)
- [ ] Test all status values (planned, active, on_hold, completed, cancelled)
- [ ] Test USD currency
- [ ] Test UGX currency
- [ ] Verify client is auto-copied from opportunity

### View Projects Tests:
- [ ] View projects for opportunity with 0 projects (empty state)
- [ ] View projects for opportunity with 1 project
- [ ] View projects for opportunity with multiple projects
- [ ] Verify all project details display correctly
- [ ] Verify opportunity info card shows correct data
- [ ] Test "Back to Opportunities" navigation

### Integration Tests:
- [ ] Auto-created project appears in projects list
- [ ] Manually created projects appear alongside auto-created
- [ ] Projects sorted by creation date
- [ ] Changing opportunity stage doesn't affect existing projects
- [ ] Total project count updates correctly

---

## 📸 Screenshot Guide

### Current Implementation (Based on Attachment)

**Opportunities Page:**
- Shows all opportunities in table format
- **NEW**: "Projects" button added to Actions column
- Displays: Client, Description, Value, Stage, Probability, Expected Close, Owner
- Actions: **Projects** | Edit | Delete

**Projects Page (New!):**
- Shows opportunity summary at top
- Lists all projects linked to that opportunity
- "Create Project (Phase)" button to add more phases
- Table shows: Name, Contract Value, Status, Dates, Lead, Created

---

## 💡 Pro Tips

1. **Auto-created vs Manual**:
   - First project when opportunity marked "won" = Auto-created
   - All subsequent projects = Manually created via UI or API

2. **Duplicate Prevention**:
   - If you change stage from won → other → won again
   - System WON'T create duplicate project
   - You'll see message: "1 existing project(s) found (IDs: 14)"

3. **Project Independence**:
   - Once created, projects have their own lifecycle
   - Changing opportunity to "lost" doesn't delete/cancel projects
   - This allows for flexibility in multi-phase scenarios

4. **Currency Defaulting**:
   - New projects default to opportunity's currency
   - Can be changed in the create form

5. **Naming Convention**:
   - Auto-created: "Client Name - Project (YYYY-MM-DD HH:MM)"
   - Manual: Whatever you specify (recommend including "Phase X")

---

## 🚀 Quick Start Commands

### View Projects for Opportunity ID 14:
```
Navigate to: http://your-domain.com/opportunities/14/projects
```

### Create Project via API:
```bash
curl -X POST http://your-domain.com/api/opportunities/14/projects \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{
    "name": "Phase 2: Enhancement",
    "contract_value": 100000,
    "contract_currency": "USD",
    "start_date": "2026-06-01",
    "end_date": "2026-12-31",
    "status": "planned"
  }'
```

### Get Projects via API:
```bash
curl http://your-domain.com/api/opportunities/14/projects \
  -H "Accept: application/json"
```

---

## ✅ Testing Complete When:

1. ✅ You can create multiple projects from the UI
2. ✅ Projects page shows all phases for an opportunity
3. ✅ Auto-created and manual projects both appear
4. ✅ Different currencies work (USD & UGX)
5. ✅ Optional end dates work (null for ongoing)
6. ✅ All status values can be set
7. ✅ Opportunity info displays correctly
8. ✅ Navigation flows smoothly

---

**Need Help?** Refer to [MANUAL_TEST_GUIDE_OPPORTUNITY_PROJECTS.md](MANUAL_TEST_GUIDE_OPPORTUNITY_PROJECTS.md) for comprehensive API testing scenarios.
