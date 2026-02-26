# OPF-CD API Contracts (LOCKED)

These contracts are FINAL.
No service, controller, or frontend may alter shapes or add wrappers.

---

## Authentication

### POST /login
Request:
{
  "email": string (required, email format),
  "password": string (required, min 6 characters)
}

Response (Success):
{
  "success": true,
  "user": {
    "id": number,
    "name": string,
    "email": string,
    "created_at": string (ISO 8601)
  },
  "message": "Authentication successful"
}

Response (Failure):
{
  "success": false,
  "user": null,
  "message": "Invalid credentials"
}

Response (Validation Error - 422):
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}

---

### POST /logout
Response:
{
  "success": true,
  "message": "Logged out successfully"
}

---

### GET /api/user/permissions
Response (Authenticated):
{
  "success": true,
  "permissions": Array<{
    "resource": string,
    "action": string
  }>,
  "roles": Array<string>
}

Example:
{
  "success": true,
  "permissions": [
    {"resource": "projects", "action": "view"},
    {"resource": "projects", "action": "create"},
    {"resource": "dashboards", "action": "view"}
  ],
  "roles": ["Admin"]
}

Response (Unauthenticated - 401):
{
  "success": false,
  "message": "Not authenticated"
}

---

### GET /api/user
Response (Authenticated):
{
  "success": true,
  "user": {
    "id": number,
    "name": string,
    "email": string
  }
}

Response (Unauthenticated - 401):
{
  "success": false,
  "message": "Not authenticated"
}

---

## Projects Management

### GET /api/projects
**Description:** List all projects

**Permission Required:** projects,view

**Response:**
Array<{
  "id": number,
  "name": string,
  "client": string,
  "contract_value": number,
  "contract_currency": "UGX" | "USD",
  "start_date": string (YYYY-MM-DD),
  "end_date": string (YYYY-MM-DD),
  "status": "planned" | "active" | "on_hold" | "completed" | "cancelled",
  "project_lead_id": number | null,
  "created_at": string (ISO 8601),
  "updated_at": string (ISO 8601)
}>

**Example:**
[
  {
    "id": 1,
    "name": "Website Redesign",
    "client": "ACME Corp",
    "contract_value": 50000,
    "contract_currency": "USD",
    "start_date": "2026-01-15",
    "end_date": "2026-06-30",
    "status": "active",
    "project_lead_id": 1,
    "created_at": "2026-01-10T08:00:00Z",
    "updated_at": "2026-02-01T10:30:00Z"
  }
]

---

### POST /api/projects
**Description:** Create new project

**Permission Required:** projects,create

**Request:**
{
  "name": string (required, max 255),
  "client": string (required, max 255),
  "contract_value": number (required, min 0),
  "contract_currency": "UGX" | "USD" (required),
  "start_date": string (required, YYYY-MM-DD),
  "end_date": string (required, YYYY-MM-DD, after_or_equal start_date),
  "status": "planned" | "active" | "on_hold" | "completed" | "cancelled" (required),
  "project_lead_id": number (optional, must exist in users table)
}

**Example Request:**
{
  "name": "Mobile App Development",
  "client": "Tech Startup Inc",
  "contract_value": 75000,
  "contract_currency": "USD",
  "start_date": "2026-03-01",
  "end_date": "2026-12-31",
  "status": "planned",
  "project_lead_id": 2
}

**Response (Success - 201):**
{
  "success": true,
  "project_id": 2,
  "message": "Project created successfully"
}

**Response (Failure - 400):**
{
  "success": false,
  "project_id": null,
  "message": "Failed to create project: <error details>"
}

**Response (Validation Error - 422):**
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "contract_value": ["The contract value must be at least 0."],
    "end_date": ["The end date must be after or equal to start date."]
  }
}

---

### GET /api/projects/{id}
**Description:** Get project details by ID

**Permission Required:** projects,view

**Response (Success):**
{
  "id": number,
  "name": string,
  "client": string,
  "contract_value": number,
  "contract_currency": "UGX" | "USD",
  "start_date": string (YYYY-MM-DD),
  "end_date": string (YYYY-MM-DD),
  "status": "planned" | "active" | "on_hold" | "completed" | "cancelled",
  "project_lead_id": number | null,
  "created_at": string (ISO 8601),
  "updated_at": string (ISO 8601)
}

**Example:**
{
  "id": 1,
  "name": "Website Redesign",
  "client": "ACME Corp",
  "contract_value": 50000,
  "contract_currency": "USD",
  "start_date": "2026-01-15",
  "end_date": "2026-06-30",
  "status": "active",
  "project_lead_id": 1,
  "created_at": "2026-01-10T08:00:00Z",
  "updated_at": "2026-02-01T10:30:00Z"
}

**Response (Not Found - 404):**
{
  "success": false,
  "message": "Project not found"
}

---

### PUT /api/projects/{id}
**Description:** Update project (enforces immutability: cannot change contract_value if payments received)

**Permission Required:** projects,edit

**Request:**
{
  "name": string (optional, max 255),
  "client": string (optional, max 255),
  "contract_value": number (optional, min 0),
  "contract_currency": "UGX" | "USD" (optional),
  "start_date": string (optional, YYYY-MM-DD),
  "end_date": string (optional, YYYY-MM-DD, after_or_equal start_date),
  "status": "planned" | "active" | "on_hold" | "completed" | "cancelled" (optional),
  "project_lead_id": number (optional, must exist in users table)
}

**Example Request (Partial Update):**
{
  "status": "completed",
  "end_date": "2026-06-15"
}

**Response (Success - 200):**
{
  "success": true,
  "message": "Project updated successfully"
}

**Response (Immutability Violation - 400):**
{
  "success": false,
  "message": "Cannot change contract value: payments have been received"
}

**Response (Not Found - 400):**
{
  "success": false,
  "message": "Project not found"
}

**Response (Validation Error - 422):**
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "end_date": ["The end date must be after or equal to start date."]
  }
}

---

### DELETE /api/projects/{id}
**Description:** Delete project (enforces immutability: cannot delete if paid milestones exist)

**Permission Required:** projects,delete

**Response (Success - 200):**
{
  "success": true,
  "message": "Project deleted successfully"
}

**Response (Immutability Violation - 400):**
{
  "success": false,
  "message": "Cannot delete project: paid milestones exist"
}

**Response (Not Found - 400):**
{
  "success": false,
  "message": "Project not found"
}

---

### GET /api/projects/{id}/has-payments
**Description:** Check if project has received payments (used by frontend to make contract_value read-only)

**Permission Required:** projects,view

**Response:**
{
  "has_payments": boolean
}

**Example:**
{
  "has_payments": true
}

---

## Tasks Management

### GET /api/projects/{projectId}/tasks
**Description:** List all tasks for a project

**Permission Required:** tasks,view

**Response:**
Array<{
  "id": number,
  "project_id": number,
  "name": string,
  "category": string | null,
  "weight": number (0-100),
  "progress": number (0-100),
  "status": "todo" | "in_progress" | "done",
  "assigned_to": number | null,
  "start_date": string (YYYY-MM-DD) | null,
  "due_date": string (YYYY-MM-DD) | null,
  "created_at": string (ISO 8601),
  "updated_at": string (ISO 8601)
}>

**Example:**
[
  {
    "id": 1,
    "project_id": 1,
    "name": "Design mockups",
    "category": "Design",
    "weight": 15,
    "progress": 80,
    "status": "in_progress",
    "assigned_to": 3,
    "start_date": "2026-02-01",
    "due_date": "2026-02-15",
    "created_at": "2026-02-01T08:00:00Z",
    "updated_at": "2026-02-05T14:30:00Z"
  }
]

---

### POST /api/projects/{projectId}/tasks
**Description:** Create new task (validates weight sum <= 100)

**Permission Required:** tasks,create

**Request:**
{
  "name": string (required, max 255),
  "category": string (optional, max 100),
  "weight": number (required, 0-100),
  "progress": number (optional, 0-100, default 0),
  "status": "todo" | "in_progress" | "done" (optional, default "todo"),
  "assigned_to": number (optional, must exist in users table),
  "start_date": string (optional, YYYY-MM-DD),
  "due_date": string (optional, YYYY-MM-DD, after_or_equal start_date)
}

**Example Request:**
{
  "name": "Implement user authentication",
  "category": "Development",
  "weight": 20,
  "progress": 0,
  "status": "todo",
  "assigned_to": 2,
  "start_date": "2026-02-10",
  "due_date": "2026-02-20"
}

**Response (Success - 201):**
{
  "success": true,
  "task_id": 2,
  "message": "Task created successfully"
}

**Response (Weight Sum Exceeds 100 - 400):**
{
  "success": false,
  "task_id": null,
  "message": "Total task weights cannot exceed 100. Current sum: 85, adding: 20"
}

**Response (Failure - 400):**
{
  "success": false,
  "task_id": null,
  "message": "Failed to create task: <error details>"
}

**Response (Validation Error - 422):**
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["Task name is required."],
    "weight": ["Task weight cannot exceed 100."],
    "due_date": ["Due date must be after or equal to start date."]
  }
}

---

### GET /api/tasks/{taskId}
**Description:** Get task details by ID

**Permission Required:** tasks,view

**Response (Success):**
{
  "id": number,
  "project_id": number,
  "name": string,
  "category": string | null,
  "weight": number,
  "progress": number,
  "status": "todo" | "in_progress" | "done",
  "assigned_to": number | null,
  "start_date": string | null,
  "due_date": string | null,
  "created_at": string (ISO 8601),
  "updated_at": string (ISO 8601)
}

**Example:**
{
  "id": 1,
  "project_id": 1,
  "name": "Design mockups",
  "category": "Design",
  "weight": 15,
  "progress": 80,
  "status": "in_progress",
  "assigned_to": 3,
  "start_date": "2026-02-01",
  "due_date": "2026-02-15",
  "created_at": "2026-02-01T08:00:00Z",
  "updated_at": "2026-02-05T14:30:00Z"
}

**Response (Not Found - 404):**
{
  "success": false,
  "message": "Task not found"
}

---

### PUT /api/tasks/{taskId}
**Description:** Update task (validates weight sum <= 100 if weight changed)

**Permission Required:** tasks,edit

**Request (all fields optional for partial update):**
{
  "name": string (max 255),
  "category": string (max 100),
  "weight": number (0-100),
  "progress": number (0-100),
  "status": "todo" | "in_progress" | "done",
  "assigned_to": number (must exist in users table),
  "start_date": string (YYYY-MM-DD),
  "due_date": string (YYYY-MM-DD, after_or_equal start_date)
}

**Example Request (Partial Update):**
{
  "progress": 100,
  "status": "done"
}

**Response (Success - 200):**
{
  "success": true,
  "message": "Task updated successfully"
}

**Response (Weight Sum Exceeds 100 - 400):**
{
  "success": false,
  "message": "Total task weights cannot exceed 100. Current sum (excluding this task): 80, new weight: 25"
}

**Response (Not Found - 400):**
{
  "success": false,
  "message": "Task not found"
}

**Response (Validation Error - 422):**
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "weight": ["Task weight cannot exceed 100."]
  }
}

---

### DELETE /api/tasks/{taskId}
**Description:** Delete task

**Permission Required:** tasks,delete

**Response (Success - 200):**
{
  "success": true,
  "message": "Task deleted successfully"
}

**Response (Not Found - 400):**
{
  "success": false,
  "message": "Task not found"
}

---

### GET /api/projects/{projectId}/tasks/weight-sum
**Description:** Get current weight sum for project (used by frontend for real-time validation)

**Permission Required:** tasks,view

**Response:**
{
  "weight_sum": number
}

**Example:**
{
  "weight_sum": 85
}

---

## Projects (Read-Only Dashboards)

### GET /projects/{id}/progress
Response:
<number>  // float 0–100

Example:
67.5

---

### GET /projects/{id}/payment-gap
Response:
{
  "gap_amount": number,
  "gap_percentage": number,
  "progress": number,
  "earned_value": number,
  "received_value": number,
  "contract_value": number
}

---

### GET /projects/{id}/health
Response:
{
  "project_id": number,
  "health_status": "green" | "amber" | "red",
  "status_label": string,
  "status_description": string,
  "score": number (0-100),
  "signals": {
    "time_score": number (0-100),
    "payment_score": number (0-100),
    "blocker_score": number (0-100),
    "overdue_score": number (0-100),
    "payment_gap_percentage": number,
    "payment_gap_amount": number,
    "earned_value": number,
    "received_value": number,
    "project_progress": number,
    "project_status": string
  },
  "reasons": string[],
  "details": string[],
  "recommendations": string[]
}

Notes:
- PHI Score = (time_score × 0.3) + (payment_score × 0.3) + (blocker_score × 0.2) + (overdue_score × 0.2)
- time_score: Actual progress vs expected progress based on timeline
- payment_score: Based on payment gap (reduced if owed money)
- blocker_score: Penalty for blocked tasks (10 points per blocked task)
- overdue_score: Penalty for overdue milestones (15 points per overdue milestone)
- Health bands: Green ≥80, Amber 50-79, Red <50
- Formula source: docs/_truth.md

---

## Finance

### GET /finance/cash-flow
Response:
{
  "cash_at_hand": number,
  "total_inflows": number,
  "total_outflows": number,
  "net_cash_flow": number,
  "average_monthly_burn": number,
  "cash_runway_months": number | null
}

---

### GET /finance/expenses/upcoming
Response:
Array<{
  "expense_id": number,
  "name": string,
  "category": string,
  "amount": number,
  "currency": string,
  "due_date": string (YYYY-MM-DD),
  "type": "one_off" | "recurring",
  "source": "original" | "projected"
}>

---

## Sales

### GET /sales/pipeline
Response:
{
  "total_pipeline_value": number,
  "weighted_pipeline_value": number,
  "opportunity_count": number,
  "by_stage": Array<{
    "stage": string,
    "count": number,
    "total_value": number,
    "weighted_value": number
  }>
}

---

## Dashboard

### GET /api/dashboard/summary
Response:
{
  "total_projects": number,
  "active_projects": number,
  "cash_at_hand": number,
  "burn_rate": number,
  "cash_runway_months": number,
  "total_pipeline_value": number,
  "total_upcoming_expenses": number,
  "health_green_count": number,
  "health_red_count": number,
  "health_amber_count": number,
  "projects_at_risk": number,
  "currency": string,
  "alert_count": number
}

Example:
{
  "total_projects": 1,
  "active_projects": 1,
  "cash_at_hand": 120000,
  "burn_rate": 8500,
  "cash_runway_months": 14.1,
  "total_pipeline_value": 320000,
  "total_upcoming_expenses": 4700,
  "health_green_count": 0,
  "health_red_count": 0,
  "health_amber_count": 1,
  "projects_at_risk": 0,
  "currency": "USD",
  "alert_count": 3
}

Notes:
- burn_rate: Average monthly outflows over last 3 months
- cash_runway_months: Cash at hand divided by burn rate (0 if burn rate is 0)
- alert_count: Number of active (non-dismissed) system alerts
- Formula from docs/_truth.md: Cash Runway (months) = Cash at Hand / Average Monthly Burn

---

## Payment Milestones Management

### GET /api/projects/{projectId}/milestones
**Description**: Get all payment milestones for a project  
**Permission**: milestones,view  
**Response**:
```json
{
  "success": true,
  "milestones": [
    {
      "id": 1,
      "project_id": 1,
      "name": "Initial Payment",
      "amount": 50000.00,
      "currency": "USD",
      "status": "paid",
      "due_date": "2026-01-15",
      "created_at": "2026-01-01T10:00:00Z",
      "updated_at": "2026-01-15T14:30:00Z",
      "is_paid": true
    },
    {
      "id": 2,
      "project_id": 1,
      "name": "Milestone 2",
      "amount": 30000.00,
      "currency": "USD",
      "status": "invoiced",
      "due_date": "2026-03-15",
      "created_at": "2026-01-01T10:00:00Z",
      "updated_at": "2026-02-01T09:00:00Z",
      "is_paid": false
    }
  ]
}
```

---

### GET /api/milestones/{milestoneId}
**Description**: Get milestone details by ID  
**Permission**: milestones,view  
**Response (Success)**:
```json
{
  "success": true,
  "milestone": {
    "id": 1,
    "project_id": 1,
    "name": "Initial Payment",
    "amount": 50000.00,
    "currency": "USD",
    "status": "paid",
    "due_date": "2026-01-15",
    "created_at": "2026-01-01T10:00:00Z",
    "updated_at": "2026-01-15T14:30:00Z",
    "is_paid": true
  }
}
```

**Response (Not Found - 404)**:
```json
{
  "success": false,
  "message": "Milestone not found."
}
```

---

### GET /api/projects/{projectId}/milestones/summary
**Description**: Get summary of milestone amounts by status and currency  
**Permission**: milestones,view  
**Response**:
```json
{
  "success": true,
  "summary": {
    "currencies": {
      "USD": {
        "pending": 20000.00,
        "invoiced": 30000.00,
        "paid": 50000.00,
        "total": 100000.00
      },
      "UGX": {
        "pending": 5000000.00,
        "invoiced": 0.00,
        "paid": 10000000.00,
        "total": 15000000.00
      }
    }
  }
}
```

---

### POST /api/projects/{projectId}/milestones
**Description**: Create a new payment milestone  
**Permission**: milestones,create  
**Request**:
```json
{
  "name": "Project Completion Payment",
  "amount": 20000.00,
  "currency": "USD",
  "status": "pending",
  "due_date": "2026-06-30"
}
```

**Response (Success - 201)**:
```json
{
  "success": true,
  "message": "Milestone created successfully.",
  "milestone_id": 3
}
```

**Response (Validation Error - 422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["Milestone name is required."],
    "amount": ["Amount must be a number."],
    "currency": ["Currency must be one of: USD, EUR, GBP, CAD, AUD, JPY, CNY, INR."],
    "due_date": ["Due date is required."]
  }
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to create milestone: [error details]",
  "milestone_id": null
}
```

---

### PUT /api/milestones/{milestoneId}
**Description**: Update an existing milestone (paid milestones are immutable)  
**Permission**: milestones,edit  
**Request**:
```json
{
  "name": "Updated Milestone Name",
  "amount": 25000.00,
  "currency": "USD",
  "status": "invoiced",
  "due_date": "2026-07-15"
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Milestone updated successfully."
}
```

**Response (Not Found - 422)**:
```json
{
  "success": false,
  "message": "Milestone not found."
}
```

**Response (Immutability Violation - 422)**:
```json
{
  "success": false,
  "message": "Cannot edit paid milestones. Financial records are immutable."
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to update milestone: [error details]"
}
```

---

### DELETE /api/milestones/{milestoneId}
**Description**: Delete a milestone (paid milestones cannot be deleted)  
**Permission**: milestones,delete  
**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Milestone deleted successfully."
}
```

**Response (Not Found - 422)**:
```json
{
  "success": false,
  "message": "Milestone not found."
}
```

**Response (Immutability Violation - 422)**:
```json
{
  "success": false,
  "message": "Cannot delete paid milestones. Financial records are immutable."
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to delete milestone: [error details]"
}
```

---

## Notes

### Milestone Immutability Rules
- Paid milestones (`status === 'paid'`) **CANNOT** be edited or deleted
- This ensures financial record integrity
- Frontend should disable edit/delete buttons for paid milestones
- Service layer enforces this rule at database level
- Status progression: `pending` → `invoiced` → `paid`

### Currency Support
Supported currencies: UGX, USD

### Status Values
- `pending`: Milestone created, payment not yet invoiced
- `invoiced`: Invoice sent to client, awaiting payment
- `paid`: Payment received, record becomes immutable

---

## Expenses Management

### GET /api/expenses
**Description**: Get all expenses (optionally filtered by project)  
**Permission**: expenses,view  
**Query Parameters**: `project_id` (optional)  
**Response**:
```json
{
  "success": true,
  "expenses": [
    {
      "id": 1,
      "name": "Office Rent",
      "category": "Infrastructure",
      "amount": 2500000.00,
      "currency": "UGX",
      "type": "recurring",
      "frequency": "monthly",
      "status": "paid",
      "project_id": null,
      "due_date": "2026-02-01",
      "created_at": "2026-01-01T10:00:00Z",
      "updated_at": "2026-02-01T09:00:00Z",
      "is_paid": true
    },
    {
      "id": 2,
      "name": "AWS Services",
      "category": "Operations",
      "amount": 1500.00,
      "currency": "USD",
      "type": "recurring",
      "frequency": "monthly",
      "status": "due",
      "project_id": 1,
      "due_date": "2026-02-15",
      "created_at": "2026-01-15T10:00:00Z",
      "updated_at": "2026-01-15T10:00:00Z",
      "is_paid": false
    },
    {
      "id": 3,
      "name": "Marketing Campaign",
      "category": "Marketing",
      "amount": 5000.00,
      "currency": "USD",
      "type": "one_off",
      "frequency": null,
      "status": "overdue",
      "project_id": 2,
      "due_date": "2026-01-30",
      "created_at": "2026-01-10T10:00:00Z",
      "updated_at": "2026-02-01T12:00:00Z",
      "is_paid": false
    }
  ]
}
```

---

### GET /api/expenses/{expenseId}
**Description**: Get expense details by ID  
**Permission**: expenses,view  
**Response (Success)**:
```json
{
  "success": true,
  "expense": {
    "id": 1,
    "name": "Office Rent",
    "category": "Infrastructure",
    "amount": 2500000.00,
    "currency": "UGX",
    "type": "recurring",
    "frequency": "monthly",
    "status": "paid",
    "project_id": null,
    "due_date": "2026-02-01",
    "created_at": "2026-01-01T10:00:00Z",
    "updated_at": "2026-02-01T09:00:00Z",
    "is_paid": true
  }
}
```

**Response (Not Found - 404)**:
```json
{
  "success": false,
  "message": "Expense not found."
}
```

---

### GET /api/expenses/summary
**Description**: Get summary of expense amounts by status and currency  
**Permission**: expenses,view  
**Query Parameters**: `project_id` (optional)  
**Response**:
```json
{
  "success": true,
  "summary": {
    "currencies": {
      "USD": {
        "due": 1500.00,
        "paid": 3000.00,
        "overdue": 5000.00,
        "total": 9500.00
      },
      "UGX": {
        "due": 1000000.00,
        "paid": 2500000.00,
        "overdue": 0.00,
        "total": 3500000.00
      }
    }
  }
}
```

---

### POST /api/expenses
**Description**: Create a new expense  
**Permission**: expenses,create  
**Request**:
```json
{
  "name": "Software License",
  "category": "Operations",
  "amount": 500.00,
  "currency": "USD",
  "type": "recurring",
  "frequency": "annual",
  "status": "due",
  "project_id": 1,
  "due_date": "2027-01-01"
}
```

**Response (Success - 201)**:
```json
{
  "success": true,
  "message": "Expense created successfully.",
  "expense_id": 4
}
```

**Response (Validation Error - 422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["Expense name is required."],
    "category": ["Category is required."],
    "amount": ["Amount must be a positive number."],
    "currency": ["Currency must be one of: UGX, USD."],
    "type": ["Type must be one of: recurring, one_off."],
    "frequency": ["Frequency is required when type is recurring."],
    "due_date": ["Due date is required."]
  }
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to create expense: [error details]",
  "expense_id": null
}
```

---

### PUT /api/expenses/{expenseId}
**Description**: Update an existing expense (paid expenses are immutable)  
**Permission**: expenses,edit  
**Request**:
```json
{
  "name": "Updated Expense Name",
  "category": "Updated Category",
  "amount": 750.00,
  "currency": "USD",
  "type": "recurring",
  "frequency": "quarterly",
  "status": "due",
  "project_id": 2,
  "due_date": "2027-03-01"
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Expense updated successfully."
}
```

**Response (Not Found - 422)**:
```json
{
  "success": false,
  "message": "Expense not found."
}
```

**Response (Immutability Violation - 422)**:
```json
{
  "success": false,
  "message": "Cannot edit paid expenses. Financial records are immutable."
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to update expense: [error details]"
}
```

---

### DELETE /api/expenses/{expenseId}
**Description**: Delete an expense (paid expenses cannot be deleted)  
**Permission**: expenses,delete  
**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Expense deleted successfully."
}
```

**Response (Not Found - 422)**:
```json
{
  "success": false,
  "message": "Expense not found."
}
```

**Response (Immutability Violation - 422)**:
```json
{
  "success": false,
  "message": "Cannot delete paid expenses. Financial records are immutable."
}
```

**Response (Database Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to delete expense: [error details]"
}
```

---

### POST /api/expenses/generate-recurring
**Description**: Generate future instances of recurring expenses  
**Permission**: expenses,create  
**Request**:
```json
{
  "months_ahead": 12
}
```

**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Recurring expenses generated successfully.",
  "instances_created": 24
}
```

**Response (Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to generate recurring expenses: [error details]",
  "instances_created": 0
}
```

---

### POST /api/expenses/update-overdue
**Description**: Update expenses with past due dates from 'due' to 'overdue' status  
**Permission**: expenses,edit  
**Response (Success - 200)**:
```json
{
  "success": true,
  "message": "Overdue expenses updated successfully.",
  "updated_count": 5
}
```

**Response (Error - 422)**:
```json
{
  "success": false,
  "message": "Failed to update overdue expenses: [error details]",
  "updated_count": 0
}
```

---

## Expense Management Notes

### Expense Immutability Rules
- Paid expenses (`status === 'paid'`) **CANNOT** be edited or deleted
- This ensures financial record integrity
- Frontend should disable edit/delete buttons for paid expenses
- Service layer enforces this rule at database level
- Status can progress: `due` → `paid` or `due` → `overdue` → `paid`

### Expense Types
- `one_off`: Single occurrence expense (frequency is null)
- `recurring`: Repeating expense with frequency (monthly, quarterly, annual)

### Expense Frequencies
- `monthly`: Repeats every month
- `quarterly`: Repeats every 3 months
- `annual`: Repeats every 12 months
- Required when `type === 'recurring'`, null otherwise

### Expense Status Values
- `due`: Expense is upcoming or current, payment not yet made
- `paid`: Expense paid, record becomes immutable
- `overdue`: Due date has passed and expense remains unpaid

### Project Association
- `project_id` can be null for company-wide expenses
- If set, expense is associated with a specific project

### Recurring Expense Generation
- Use `/api/expenses/generate-recurring` to create future instances
- System checks for duplicates (same expense, same month/year)
- Instances inherit all properties from parent expense except due_date
- Status automatically set based on due_date (past = overdue, future = due)

### Currency Support
Supported currencies: UGX, USD

---

## Opportunities Management

### GET /api/opportunities
Retrieve all opportunities ordered by expected close date.

**Permissions**: `opportunities,view`

Request: None

Response (Success - 200):
```json
[
  {
    "id": 1,
    "client": "ABC Corporation",
    "description": "Website redesign project",
    "estimated_value": 25000000.00,
    "currency": "UGX",
    "probability": 75.00,
    "stage": "proposal",
    "source": "Referral",
    "owner": 2,
    "owner_email": "sales@example.com",
    "expected_close_date": "2025-03-15",
    "created_at": "2025-01-10 08:00:00",
    "updated_at": "2025-01-15 14:30:00"
  },
  {
    "id": 2,
    "client": "XYZ Industries",
    "description": "Mobile app development",
    "estimated_value": 45000000.00,
    "currency": "UGX",
    "probability": 50.00,
    "stage": "negotiation",
    "source": "LinkedIn",
    "owner": 3,
    "owner_email": "manager@example.com",
    "expected_close_date": "2025-04-01",
    "created_at": "2025-01-12 10:15:00",
    "updated_at": "2025-01-18 16:45:00"
  }
]
```

---

### GET /api/opportunities/{opportunityId}
Retrieve details of a specific opportunity.

**Permissions**: `opportunities,view`

Request: None

Response (Success - 200):
```json
{
  "id": 1,
  "client": "ABC Corporation",
  "description": "Website redesign project",
  "estimated_value": 25000000.00,
  "currency": "UGX",
  "probability": 75.00,
  "stage": "proposal",
  "source": "Referral",
  "owner": 2,
  "owner_email": "sales@example.com",
  "expected_close_date": "2025-03-15",
  "created_at": "2025-01-10 08:00:00",
  "updated_at": "2025-01-15 14:30:00"
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Opportunity not found."
}
```

---

### POST /api/opportunities
Create a new sales opportunity.

**Permissions**: `opportunities,create`

Request:
```json
{
  "client": "ABC Corporation",
  "description": "Website redesign project",
  "estimated_value": 25000000.00,
  "currency": "UGX",
  "probability": 75.00,
  "stage": "proposal",
  "source": "Referral",
  "owner": 2,
  "expected_close_date": "2025-03-15"
}
```

**Validation Rules**:
- `client`: required, string, max 255 characters
- `description`: required, string, max 255 characters
- `estimated_value`: required, numeric, >= 0
- `currency`: required, string, one of: `UGX`, `USD`
- `probability`: required, numeric, 0-100
- `stage`: required, one of: `lead`, `qualified`, `proposal`, `negotiation`, `won`, `lost`
- `source`: required, string, max 100 characters
- `owner`: required, integer, must exist in users table
- `expected_close_date`: required, date, must be today or later (only on create)

Response (Success - 201):
```json
{
  "success": true,
  "message": "Opportunity created successfully.",
  "opportunity_id": 5
}
```

Response (Validation Error - 422):
```json
{
  "message": "Validation failed",
  "errors": {
    "client": ["Client name is required."],
    "probability": ["Probability must be at least 0%."],
    "stage": ["Stage must be one of: lead, qualified, proposal, negotiation, won, or lost."]
  }
}
```

Response (Server Error - 500):
```json
{
  "success": false,
  "message": "Failed to create opportunity: [error details]",
  "opportunity_id": null
}
```

---

### PUT /api/opportunities/{opportunityId}
Update an existing opportunity.

**Permissions**: `opportunities,edit`

Request (partial updates allowed via `sometimes` validation):
```json
{
  "client": "ABC Corporation Ltd",
  "probability": 85.00,
  "stage": "negotiation"
}
```

**Validation Rules**:
All fields use `sometimes` modifier (only validate if present):
- `client`: required if present, string, max 255 characters
- `description`: required if present, string, max 255 characters
- `estimated_value`: required if present, numeric, >= 0
- `currency`: required if present, string, one of: `UGX`, `USD`
- `probability`: required if present, numeric, 0-100
- `stage`: required if present, one of: `lead`, `qualified`, `proposal`, `negotiation`, `won`, `lost`
- `source`: required if present, string, max 100 characters
- `owner`: required if present, integer, must exist in users table
- `expected_close_date`: required if present, date (no date restriction on edit)

Response (Success - 200):
```json
{
  "success": true,
  "message": "Opportunity updated successfully."
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Opportunity not found."
}
```

Response (Validation Error - 422):
```json
{
  "message": "Validation failed",
  "errors": {
    "probability": ["Probability cannot exceed 100%."],
    "owner": ["Selected owner does not exist."]
  }
}
```

Response (Server Error - 500):
```json
{
  "success": false,
  "message": "Failed to update opportunity: [error details]"
}
```

---

### DELETE /api/opportunities/{opportunityId}
Delete an opportunity.

**Permissions**: `opportunities,delete`

Request: None

Response (Success - 200):
```json
{
  "success": true,
  "message": "Opportunity deleted successfully."
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Opportunity not found."
}
```

Response (Server Error - 500):
```json
{
  "success": false,
  "message": "Failed to delete opportunity: [error details]"
}
```

---

### Opportunity Stage Values
- `lead`: Initial contact, not yet qualified
- `qualified`: Confirmed as potential customer with budget/need
- `proposal`: Formal proposal submitted
- `negotiation`: In negotiation phase (pricing, terms, scope)
- `won`: Opportunity closed successfully
- `lost`: Opportunity lost to competitor or rejected

### Probability Rules
- Must be numeric value between 0 and 100 (inclusive)
- Represents percentage chance of closing the deal
- Used in weighted pipeline calculations (estimated_value * probability)

### Owner Constraint
- Owner field references `users` table
- Owner cannot be deleted while assigned to opportunities (ON DELETE RESTRICT)
- Owner email displayed alongside opportunity data via join

### Expected Close Date
- Create: Must be today or later (enforced by validation)
- Edit: No date restriction (allows updating past opportunities)
- Used to order opportunities (ASC by expected_close_date, then by created_at DESC)

---

## Accounts & Cash Transactions Management

### GET /api/accounts
Retrieve all accounts.

Response (Success - 200):
```json
[
  {
    "id": 1,
    "name": "Stanbic Bank Business Account",
    "type": "bank",
    "currency": "UGX",
    "opening_balance": 5000000,
    "created_at": "2026-02-05T10:30:00Z",
    "updated_at": "2026-02-05T10:30:00Z"
  },
  {
    "id": 2,
    "name": "MTN Mobile Money",
    "type": "mobile_money",
    "currency": "UGX",
    "opening_balance": 250000,
    "created_at": "2026-02-05T11:15:00Z",
    "updated_at": "2026-02-05T11:15:00Z"
  }
]
```

---

### GET /api/accounts/{accountId}
Retrieve single account details.

Response (Success - 200):
```json
{
  "id": 1,
  "name": "Stanbic Bank Business Account",
  "type": "bank",
  "currency": "UGX",
  "opening_balance": 5000000,
  "created_at": "2026-02-05T10:30:00Z",
  "updated_at": "2026-02-05T10:30:00Z"
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Account not found."
}
```

---

### POST /api/accounts
Create a new account.

Request:
```json
{
  "name": "Petty Cash",
  "type": "cash",
  "currency": "USD",
  "opening_balance": 500
}
```

Validation Rules:
- `name`: required, string, max 255 characters
- `type`: required, enum (bank|mobile_money|cash)
- `currency`: required, enum (UGX|USD)
- `opening_balance`: required, numeric, min 0

Response (Success - 200):
```json
{
  "success": true,
  "message": "Account created successfully.",
  "account_id": 3
}
```

Response (Validation Error - 422):
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "type": ["The selected type is invalid."],
    "opening_balance": ["The opening balance must be at least 0."]
  }
}
```

---

### PUT /api/accounts/{accountId}
Update existing account (partial updates supported).

Request:
```json
{
  "name": "Stanbic Bank Updated Name"
}
```

Validation Rules (all optional via "sometimes" modifier):
- `name`: string, max 255 characters
- `type`: enum (bank|mobile_money|cash)
- `currency`: enum (UGX|USD)
- `opening_balance`: numeric, min 0

Response (Success - 200):
```json
{
  "success": true,
  "message": "Account updated successfully."
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Account not found."
}
```

---

### DELETE /api/accounts/{accountId}
Delete an account.

Response (Success - 200):
```json
{
  "success": true,
  "message": "Account deleted successfully."
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Account not found."
}
```

Response (Constraint Violation - 500):
```json
{
  "success": false,
  "message": "Failed to delete account: [error details]"
}
```

Note: Deletion fails if transactions exist for this account (ON DELETE RESTRICT in database).

---

### GET /api/cash-transactions
Retrieve all cash transactions, optionally filtered by account.

Query Parameters:
- `account_id` (optional): Filter transactions for specific account

Response (Success - 200):
```json
[
  {
    "id": 1,
    "account_id": 1,
    "account_name": "Stanbic Bank Business Account",
    "account_type": "bank",
    "type": "inflow",
    "amount": 100000,
    "currency": "UGX",
    "source_type": "project_payment",
    "source_id": 1,
    "transaction_date": "2026-02-05",
    "created_at": "2026-02-05T14:20:00Z"
  },
  {
    "id": 2,
    "account_id": 1,
    "account_name": "Stanbic Bank Business Account",
    "account_type": "bank",
    "type": "outflow",
    "amount": 25000,
    "currency": "UGX",
    "source_type": "expense",
    "source_id": 5,
    "transaction_date": "2026-02-05",
    "created_at": "2026-02-05T15:45:00Z"
  }
]
```

---

### GET /api/cash-transactions/{transactionId}
Retrieve single transaction details.

Response (Success - 200):
```json
{
  "id": 1,
  "account_id": 1,
  "account_name": "Stanbic Bank Business Account",
  "account_type": "bank",
  "type": "inflow",
  "amount": 100000,
  "currency": "UGX",
  "source_type": "project_payment",
  "source_id": 1,
  "transaction_date": "2026-02-05",
  "created_at": "2026-02-05T14:20:00Z"
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Transaction not found."
}
```

---

### POST /api/cash-transactions
Record a new cash transaction.

Request:
```json
{
  "account_id": 1,
  "type": "inflow",
  "amount": 150000,
  "currency": "UGX",
  "source_type": "project_payment",
  "source_id": 2,
  "transaction_date": "2026-02-05"
}
```

Validation Rules:
- `account_id`: required, integer, must exist in accounts table
- `type`: required, enum (inflow|outflow)
- `amount`: required, numeric, must be greater than 0
- `currency`: required, enum (UGX|USD)
- `source_type`: required, string, max 50 characters
- `source_id`: required, integer
- `transaction_date`: required, date

Response (Success - 200):
```json
{
  "success": true,
  "message": "Transaction recorded successfully.",
  "transaction_id": 3
}
```

Response (Validation Error - 422):
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "amount": ["The amount must be greater than 0."],
    "account_id": ["The selected account id is invalid."]
  }
}
```

---

### Account Type Values
- `bank`: Traditional bank account
- `mobile_money`: Mobile money service (e.g., MTN, Airtel)
- `cash`: Physical cash holdings

### Transaction Type Values
- `inflow`: Money received (increases balance)
- `outflow`: Money paid (decreases balance)

### Currency Values
- `UGX`: Ugandan Shilling
- `USD`: US Dollar

### Balance Calculation
- **Opening Balance**: Initial balance when account tracking started (stored in `accounts.opening_balance`)
- **Current Balance**: Calculated value = opening_balance + SUM(inflows) - SUM(outflows)
- Services return ONLY `opening_balance` from database (NO balance calculations)
- Current balance computed elsewhere (future CashFlowService or reporting layer)

### Transaction Immutability
- Cash transactions are **append-only audit records**
- NO update or delete operations allowed via API
- Once recorded, transactions cannot be modified (enforced at database and application layer)
- Schema has `created_at` but NO `updated_at` column
- Database comment: "append-only audit records - never updated or deleted"

### Account Constraints
- Opening balance must be >= 0 (CHECK constraint)
- Accounts cannot be deleted if transactions exist (ON DELETE RESTRICT)
- Account ID required for all transactions (foreign key constraint)

### Ordering & Filtering
- **Accounts**: Ordered by `created_at` DESC (newest first)
- **Transactions**: Ordered by `transaction_date` DESC, then `created_at` DESC
- **Filtering**: GET /api/cash-transactions accepts optional `?account_id=X` query parameter

---

## Alerts

### GET /api/alerts
Get all active (non-dismissed) system alerts.

Permission Required: `dashboards:view`

Response (Success - 200):
```json
{
  "success": true,
  "alerts": [
    {
      "id": 1,
      "type": "project_behind_schedule",
      "severity": "warning",
      "entity_type": "project",
      "entity_id": 5,
      "message": "Project 'Website Redesign' is behind schedule (time_score: 45). Expected: 60%, Actual: 45%.",
      "is_dismissed": false,
      "dismissed_at": null,
      "dismissed_by": null,
      "created_at": "2025-01-15T08:30:00Z"
    },
    {
      "id": 2,
      "type": "low_cash_runway",
      "severity": "critical",
      "entity_type": "system",
      "entity_id": null,
      "message": "Cash runway critically low: 1.8 months remaining. Consider reducing burn rate or securing additional funding.",
      "is_dismissed": false,
      "dismissed_at": null,
      "dismissed_by": null,
      "created_at": "2025-01-15T06:00:00Z"
    }
  ]
}
```

---

### GET /api/alerts/count
Get count of active alerts by severity level.

Permission Required: `dashboards:view`

Response (Success - 200):
```json
{
  "total": 8,
  "critical": 2,
  "warning": 5,
  "info": 1
}
```

---

### POST /api/alerts/{alertId}/dismiss
Mark an alert as dismissed (acknowledged).

Permission Required: `dashboards:view`

Response (Success - 200):
```json
{
  "success": true,
  "message": "Alert dismissed successfully"
}
```

Response (Not Found - 404):
```json
{
  "success": false,
  "message": "Alert not found"
}
```

### Alert Fields
- **id**: Unique alert identifier
- **type**: Alert type enum
  - `project_behind_schedule`: Project time_score < 60
  - `payment_gap_breach`: Payment gap > 20%
  - `low_cash_runway`: Cash runway < 3 months
  - `expense_overdue`: Expense past due_date
  - `opportunity_closing_soon`: Opportunity < 7 days to expected_close_date
- **severity**: Alert severity enum
  - `critical`: Requires immediate attention
  - `warning`: Should be addressed soon
  - `info`: Informational only
- **entity_type**: Type of entity alert relates to (`project`, `expense`, `opportunity`, `system`)
- **entity_id**: ID of related entity (null for system-wide alerts)
- **message**: Human-readable alert description
- **is_dismissed**: Whether alert has been acknowledged
- **dismissed_at**: Timestamp when dismissed (null if not dismissed)
- **dismissed_by**: User ID who dismissed (null if not dismissed)
- **created_at**: Alert creation timestamp

### Alert Behavior
- Alerts are auto-generated daily by scheduled command (`php artisan alerts:evaluate`)
- Duplicate prevention: No more than 1 alert per type+entity within 7 days
- Auto-cleanup: Dismissed alerts older than 30 days are automatically removed
- Dismissal is user-specific tracking (does not delete alert)
- New alerts of same type can be created after 7 days

---

## Audit Logs

### GET /api/audit-logs
Get audit logs with optional filters.

Permission Required: `dashboards:view`

Query Parameters:
- `entity_type`: Filter by entity type (e.g., "projects", "tasks", "expenses")
- `entity_id`: Filter by specific entity ID
- `action`: Filter by action type ("create", "update", "delete")
- `user_id`: Filter by user who performed the action
- `from_date`: Start date (YYYY-MM-DD format)
- `to_date`: End date (YYYY-MM-DD format)
- `limit`: Maximum number of records (default 100, max 500)

Response (Success - 200):
```json
{
  "success": true,
  "logs": [
    {
      "id": 1,
      "user_id": 5,
      "user_name": "John Admin",
      "user_email": "john@example.com",
      "action": "update",
      "entity_type": "projects",
      "entity_id": 3,
      "changes": {
        "before": {
          "status": "active",
          "end_date": "2026-06-30"
        },
        "after": {
          "status": "completed",
          "end_date": "2026-02-14"
        },
        "changed_fields": ["status", "end_date"]
      },
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-02-14T10:30:00Z"
    },
    {
      "id": 2,
      "user_id": 3,
      "user_name": "Jane User",
      "user_email": "jane@example.com",
      "action": "create",
      "entity_type": "expenses",
      "entity_id": 15,
      "changes": {
        "after": {
          "amount": 1500,
          "description": "Server hosting",
          "due_date": "2026-03-01"
        }
      },
      "ip_address": "192.168.1.105",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-02-14T09:15:00Z"
    }
  ],
  "count": 2
}
```

---

### GET /api/audit-logs/entity/{entityType}/{entityId}
Get audit logs for a specific entity.

Permission Required: `dashboards:view`

Path Parameters:
- `entityType`: Entity type (e.g., "projects", "tasks")
- `entityId`: Entity ID

Query Parameters:
- `limit`: Maximum number of records (default 50, max 200)

Response (Success - 200):
```json
{
  "success": true,
  "entity_type": "projects",
  "entity_id": 3,
  "logs": [
    {
      "id": 1,
      "user_id": 5,
      "action": "update",
      "entity_type": "projects",
      "entity_id": 3,
      "changes": {
        "before": {"status": "active"},
        "after": {"status": "completed"},
        "changed_fields": ["status"]
      },
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-02-14T10:30:00Z"
    }
  ],
  "count": 1
}
```

---

### GET /api/audit-logs/user/{userId}
Get audit logs for a specific user.

Permission Required: `dashboards:view`

Path Parameters:
- `userId`: User ID

Query Parameters:
- `limit`: Maximum number of records (default 50, max 200)

Response (Success - 200):
```json
{
  "success": true,
  "user_id": 5,
  "logs": [
    {
      "id": 1,
      "user_id": 5,
      "action": "update",
      "entity_type": "projects",
      "entity_id": 3,
      "changes": {
        "before": {"status": "active"},
        "after": {"status": "completed"},
        "changed_fields": ["status"]
      },
      "ip_address": "192.168.1.100",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2026-02-14T10:30:00Z"
    }
  ],
  "count": 1
}
```

---

### GET /api/audit-logs/stats
Get audit log statistics.

Permission Required: `dashboards:view`

Query Parameters (all optional):
- `entity_type`: Filter by entity type
- `user_id`: Filter by user
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)

Response (Success - 200):
```json
{
  "total": 1547,
  "creates": 523,
  "updates": 891,
  "deletes": 133
}
```

### Audit Log Fields
- **id**: Unique audit log identifier
- **user_id**: ID of user who performed the action
- **user_name**: Name of user (joined from users table)
- **user_email**: Email of user (joined from users table)
- **action**: Action type enum ("create", "update", "delete")
- **entity_type**: Type of entity modified (table name: "projects", "tasks", "expenses", etc.)
- **entity_id**: ID of the modified entity
- **changes**: JSONB object containing:
  - For **create**: `after` field with created record data
  - For **update**: `before` and `after` fields with changed data, plus `changed_fields` array
  - For **delete**: `before` field with deleted record's final state
- **ip_address**: IP address of user (nullable)
- **user_agent**: Browser/client user agent (nullable)
- **created_at**: Timestamp when action occurred

### Audit Log Behavior
- Logs are immutable (append-only, never updated or deleted)
- Captured automatically by services when data changes
- Includes request context (IP, user agent) when available
- Update logs only created if actual changes detected (excludes `updated_at` field)
- No duplicate detection - all actions logged independently
- No automatic cleanup - logs retained indefinitely for compliance

### Entity Types
Valid entity types correspond to database table names:
- `projects`: Project records
- `tasks`: Task records
- `payment_milestones`: Payment milestone records
- `expenses`: Expense records
- `opportunities`: Sales opportunity records
- `accounts`: Cash account records
- `cash_transactions`: Cash transaction records
- `users`: User account records

---

## Report Exports

All export endpoints return CSV files as direct downloads with appropriate Content-Type and Content-Disposition headers.

Permission Required: `dashboards:view` (for all export endpoints)

### GET /api/reports/export/dashboard
Export dashboard summary as CSV.

Query Parameters:
- `currency`: Currency code (default: USD)

Response: CSV file download
- Filename format: `opf_cd_dashboard_summary_YYYY-MM-DD_HHMMSS.csv`
- Content-Type: `text/csv`
- Includes: All dashboard KPIs (projects, cash, burn rate, runway, pipeline, expenses, health, alerts)

---

### GET /api/reports/export/projects
Export projects list as CSV.

Query Parameters:
- `status`: Filter by status (e.g., "active", "completed")
- `client`: Filter by client name (partial match)

Response: CSV file download
- Filename format: `opf_cd_projects_YYYY-MM-DD_HHMMSS.csv`
- Columns: ID, Name, Client, Status, Start Date, End Date, Contract Value, Currency, Created

---

### GET /api/reports/export/cash-flow
Export cash flow transactions as CSV.

Query Parameters:
- `currency`: Currency code (default: USD)
- `start_date`: Start date filter (YYYY-MM-DD)
- `end_date`: End date filter (YYYY-MM-DD)

Response: CSV file download
- Filename format: `opf_cd_cash_flow_YYYY-MM-DD_HHMMSS.csv`
- Columns: ID, Date, Type, Amount, Currency, Description, Account, Created
- Includes summary rows: Total Inflows, Total Outflows, Net Cash Flow

---

### GET /api/reports/export/opportunities
Export sales pipeline opportunities as CSV.

Query Parameters:
- `stage`: Filter by stage
- `min_probability`: Minimum close probability (0-100)

Response: CSV file download
- Filename format: `opf_cd_opportunities_YYYY-MM-DD_HHMMSS.csv`
- Columns: ID, Name, Client, Stage, Value, Currency, Close Probability (%), Expected Close Date, Created
- Includes summary rows: Total Pipeline Value, Weighted Value

---

### GET /api/reports/export/expenses
Export expenses report as CSV.

Query Parameters:
- `status`: Filter by status (e.g., "due", "paid")
- `type`: Filter by type
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)

Response: CSV file download
- Filename format: `opf_cd_expenses_YYYY-MM-DD_HHMMSS.csv`
- Columns: ID, Description, Amount, Currency, Type, Status, Due Date, Project, Created
- Includes summary by status

---

### GET /api/reports/export/audit-logs
Export audit logs as CSV.

Query Parameters:
- `entity_type`: Filter by entity type
- `action`: Filter by action type
- `user_id`: Filter by user
- `from_date`: Start date (YYYY-MM-DD)
- `to_date`: End date (YYYY-MM-DD)
- `limit`: Maximum records (default: 500, max: 10000)

Response: CSV file download
- Filename format: `opf_cd_audit_logs_YYYY-MM-DD_HHMMSS.csv`
- Columns: ID, User, Action, Entity Type, Entity ID, IP Address, Timestamp, Changes Summary

---

### GET /api/reports/export/project-health
Export project health scores as CSV.

No query parameters required.

Response: CSV file download
- Filename format: `opf_cd_project_health_YYYY-MM-DD_HHMMSS.csv`
- Columns: Project ID, Project Name, Client, PHI Score, Time Score, Payment Score, Blocker Score, Overdue Score, Health Status
- Only includes active projects

### Export Features
- **Immediate Download**: Files returned as direct HTTP response (not stored on server)
- **Timestamp Naming**: All filenames include generation timestamp to prevent overwrites
- **CSV Format**: Universal compatibility (Excel, Google Sheets, data analysis tools)
- **Summary Rows**: Financial reports include calculated totals where applicable
- **Filter Preservation**: Exports respect query parameter filters for targeted reports
- **No Pagination**: Single file contains all matching records (up to defined limits)

### Usage Examples

Export dashboard summary:
```
GET /api/reports/export/dashboard?currency=USD
```

Export active projects:
```
GET /api/reports/export/projects?status=active
```

Export cash flow for Q1 2026:
```
GET /api/reports/export/cash-flow?currency=USD&start_date=2026-01-01&end_date=2026-03-31
```

Export high-probability opportunities:
```
GET /api/reports/export/opportunities?min_probability=70
```

Export overdue expenses:
```
GET /api/reports/export/expenses?status=due&to_date=2026-02-14
```

Export recent audit logs:
```
GET /api/reports/export/audit-logs?from_date=2026-02-01&limit=1000
```

---
