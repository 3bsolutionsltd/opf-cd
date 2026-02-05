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
  "score": number,
  "signals": object,
  "reasons": string[]
}

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
  "total_pipeline_value": number,
  "total_upcoming_expenses": number,
  "average_project_health": "green" | "amber" | "red",
  "projects_at_risk": number,
  "currency": string
}

Example:
{
  "total_projects": 1,
  "active_projects": 1,
  "cash_at_hand": 120000,
  "total_pipeline_value": 320000,
  "total_upcoming_expenses": 4700,
  "average_project_health": "amber",
  "projects_at_risk": 0,
  "currency": "USD"
}

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

