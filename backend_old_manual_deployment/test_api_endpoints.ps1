#!/usr/bin/env pwsh

# Phase 5.4.5 Day 3-4: API Endpoint Testing via curl (PowerShell version)
# This script tests all 13 template endpoints
# Run from: backend_old_manual_deployment directory

$BASE_URL = "http://127.0.0.1:8000"
$API_BASE = "$BASE_URL/api"

# Test counters
$PASSED = 0
$FAILED = 0

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Phase 5.4.5: API Endpoint Testing" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Function to test endpoints
function Test-Endpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [string]$Body,
        [string]$ExpectedStatus,
        [string]$Description
    )

    Write-Host "Testing: $Description" -ForegroundColor Yellow
    Write-Host "  $Method $Endpoint" -ForegroundColor Gray

    try {
        $params = @{
            Uri = "$API_BASE$Endpoint"
            Method = $Method
            Headers = @{ "Content-Type" = "application/json" }
            SkipHttpErrorCheck = $true
        }

        if ($Body) {
            $params['Body'] = $Body
        }

        $response = Invoke-WebRequest @params
        $httpCode = $response.StatusCode

        if ($httpCode -match $ExpectedStatus) {
            Write-Host "  ✓ PASS (HTTP $httpCode)" -ForegroundColor Green
            $script:PASSED++
        } else {
            Write-Host "  ✗ FAIL (Expected $ExpectedStatus, got HTTP $httpCode)" -ForegroundColor Red
            $script:FAILED++
        }

        # Show response for debugging
        if ($httpCode -notin (200, 201)) {
            $preview = $response.Content.Substring(0, [Math]::Min(100, $response.Content.Length))
            Write-Host "  Response: $preview" -ForegroundColor Gray
        }
    } catch {
        Write-Host "  ✗ FAIL (Exception: $($_.Exception.Message))" -ForegroundColor Red
        $script:FAILED++
    }

    Write-Host ""
}

# ========== PUBLIC ENDPOINTS ==========
Write-Host "=== PUBLIC ENDPOINTS ===" -ForegroundColor Yellow
Write-Host ""

# 1. GET /api/templates
Test-Endpoint -Method "GET" -Endpoint "/templates" -ExpectedStatus "200" `
    -Description "1. GET /api/templates - List all active templates"

# 2. GET /api/templates/{id}
Test-Endpoint -Method "GET" -Endpoint "/templates/1" -ExpectedStatus "200" `
    -Description "2. GET /api/templates/1 - Get Web App template with tasks"

# 3. GET /api/templates/{id}/preview
Test-Endpoint -Method "GET" -Endpoint "/templates/1/preview" -ExpectedStatus "200" `
    -Description "3. GET /api/templates/1/preview - Preview tasks"

# 4. Invalid template
Test-Endpoint -Method "GET" -Endpoint "/templates/99999" -ExpectedStatus "404" `
    -Description "4. GET /api/templates/99999 - Invalid template (expect 404)"

# 5. Verify all templates exist
Write-Host "Verifying all 5 templates:" -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$API_BASE/templates" -Method GET
    $templates = $response.Content | ConvertFrom-Json
    $count = $templates.data.Count
    Write-Host "  Found $count active templates" -ForegroundColor Green
    $templates.data | ForEach-Object { Write-Host "    - $($_.name)" }
} catch {
    Write-Host "  Error listing templates: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# ========== PROJECT CREATION WITH TEMPLATE ==========
Write-Host "=== PROJECT CREATION WITH TEMPLATE ===" -ForegroundColor Yellow
Write-Host ""

# Create test opportunity
Write-Host "Creating test opportunity..." -ForegroundColor Gray
$timestamp = [System.DateTime]::Now.Ticks / 10000000
$OPP_JSON = @{
    name = "Test Opportunity $timestamp"
    estimated_value = 10000
    currency = "USD"
    stage = "won"
    probability = 100
    user_id = 1
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$API_BASE/opportunities" -Method POST `
        -Body $OPP_JSON -Headers @{ "Content-Type" = "application/json" } -SkipHttpErrorCheck
    $data = $response.Content | ConvertFrom-Json
    $OPP_ID = $data.data.id
    Write-Host "Opportunity ID: $OPP_ID" -ForegroundColor Green
} catch {
    Write-Host "Failed to create opportunity" -ForegroundColor Red
    $OPP_ID = 1
}

# 5. Create project with template
if ($OPP_ID) {
    $CREATE_PROJECT = @{
        template_id = 1
        project_name = "Test Web App Project"
    } | ConvertTo-Json

    Test-Endpoint -Method "POST" -Endpoint "/opportunities/$OPP_ID/projects/with-template" `
        -Body $CREATE_PROJECT -ExpectedStatus "201|200" `
        -Description "5. POST /opportunities/{id}/projects/with-template - Create with Web App"
}

# 6. Validation error test
Test-Endpoint -Method "POST" -Endpoint "/opportunities/1/projects/with-template" `
    -Body "{}" -ExpectedStatus "422|400" `
    -Description "6. POST with missing fields - Validation error"

# 7. Invalid opportunity
Test-Endpoint -Method "POST" -Endpoint "/opportunities/99999/projects/with-template" `
    -Body '{"template_id": 1, "project_name": "Invalid"}' -ExpectedStatus "404|422" `
    -Description "7. POST with invalid opportunity"

Write-Host ""

# ========== APPLY TEMPLATE TO PROJECT ==========
Write-Host "=== APPLY TEMPLATE TO PROJECT ===" -ForegroundColor Yellow
Write-Host ""

Write-Host "Creating test project..." -ForegroundColor Gray
$PROJECT_JSON = @{
    name = "Empty Test Project"
    contract_value = 5000
    currency = "USD"
    start_date = "2026-02-28"
    end_date = "2026-03-31"
    user_id = 1
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "$API_BASE/projects" -Method POST `
        -Body $PROJECT_JSON -Headers @{ "Content-Type" = "application/json" } -SkipHttpErrorCheck
    $data = $response.Content | ConvertFrom-Json
    $PROJ_ID = $data.data.id
    Write-Host "Project ID: $PROJ_ID" -ForegroundColor Green
    
    if ($PROJ_ID) {
        $APPLY_TPL = @{ template_id = 2 } | ConvertTo-Json
        Test-Endpoint -Method "POST" -Endpoint "/projects/$PROJ_ID/apply-template" `
            -Body $APPLY_TPL -ExpectedStatus "200" `
            -Description "8. POST /projects/{id}/apply-template - Apply Mobile template"
    }
} catch {
    Write-Host "Failed to create project: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""

# ========== ADMIN ENDPOINTS ==========
Write-Host "=== ADMIN ENDPOINTS ===" -ForegroundColor Yellow
Write-Host ""

# 9. Admin list templates
Test-Endpoint -Method "GET" -Endpoint "/admin/templates" -ExpectedStatus "200|403" `
    -Description "9. GET /admin/templates - List all (expect 200 or 403)"

# 10. Create template
$timestamp = [System.DateTime]::Now.Ticks / 10000000
$CREATE_TPL = @{
    name = "Custom Template $timestamp"
    description = "Test template"
    category = "Custom"
    is_active = $true
    task_count = 0
    average_duration_days = 30
} | ConvertTo-Json

Test-Endpoint -Method "POST" -Endpoint "/admin/templates" `
    -Body $CREATE_TPL -ExpectedStatus "201|403" `
    -Description "10. POST /admin/templates - Create template (expect 201 or 403)"

# 11. Update template
Test-Endpoint -Method "PUT" -Endpoint "/admin/templates/1" `
    -Body '{"is_active":false}' -ExpectedStatus "200|403" `
    -Description "11. PUT /admin/templates/1 - Update template"

# 12. Add task to template
$ADD_TASK = @{
    name = "New Task"
    description = "Test task"
    weight = 15
    phase_number = 1
} | ConvertTo-Json

Test-Endpoint -Method "POST" -Endpoint "/admin/templates/1/tasks" `
    -Body $ADD_TASK -ExpectedStatus "201|403" `
    -Description "12. POST /admin/templates/1/tasks - Add task"

# 13. Delete invalid template
Test-Endpoint -Method "DELETE" -Endpoint "/admin/templates/999" `
    -ExpectedStatus "404|403" `
    -Description "13. DELETE /admin/templates/999 - Invalid template"

Write-Host ""

# ========== PERFORMANCE TESTING ==========
Write-Host "=== PERFORMANCE BENCHMARKS ===" -ForegroundColor Yellow
Write-Host ""

Write-Host "Testing response times (should be [less than] 200ms for GET, [less than] 500ms for POST):" -ForegroundColor Gray

for ($i = 1; $i -le 3; $i++) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    $response = Invoke-WebRequest -Uri "$API_BASE/templates" -Method GET
    $sw.Stop()
    
    Write-Host "  Iteration $i : GET /api/templates = $($sw.ElapsedMilliseconds)ms" -ForegroundColor Gray
}

Write-Host ""

# ========== SUMMARY ==========
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Test Summary:" -ForegroundColor Cyan
Write-Host "  Passed: $PASSED" -ForegroundColor Green
Write-Host "  Failed: $FAILED" -ForegroundColor Red

if ($FAILED -eq 0) {
    Write-Host "✓ All tests passed!" -ForegroundColor Green
    exit 0
} else {
    Write-Host "✗ Some tests failed" -ForegroundColor Red
    exit 1
}
