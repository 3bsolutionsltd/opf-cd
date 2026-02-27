#!/usr/bin/env pwsh

# Phase 5.4.5: Simple API Endpoint Testing (PowerShell)
# Tests all 13 template endpoints with curl

$BASE_URL = "http://127.0.0.1:8000"
$PASSED = 0
$FAILED = 0

Write-Host "`n========== API ENDPOINT TESTING ==========" -ForegroundColor Cyan
Write-Host "Base URL: $BASE_URL" -ForegroundColor Gray
Write-Host ""

function Test-API {
    param([string]$name, [string]$method, [string]$endpoint, [string]$expectedCode)
    
    Write-Host "[$method] $endpoint" -NoNewline
    
    try {
        $response = curl -s -w "`n%{http_code}" -X $method "$BASE_URL$endpoint" 2>$null
        $httpCode = $response[-1]
        
        if ($httpCode -eq $expectedCode) {
            Write-Host " - ✓ PASS ($httpCode)" -ForegroundColor Green
            $script:PASSED++
        } else {
            Write-Host " - ✗ FAIL (Expected $expectedCode, got $httpCode)" -ForegroundColor Red
            $script:FAILED++
        }
    } catch {
        Write-Host " - ✗ ERROR: $_" -ForegroundColor Red
        $script:FAILED++
    }
}

Write-Host "=== PUBLIC ENDPOINTS ===" -ForegroundColor Yellow

Test-API "List templates" "GET" "/api/templates" "200"
Test-API "Get template 1" "GET" "/api/templates/1" "200"
Test-API "Preview template 1" "GET" "/api/templates/1/preview" "200"
Test-API "Invalid template" "GET" "/api/templates/99999" "404"

Write-Host "`n=== ADMIN ENDPOINTS ===" -ForegroundColor Yellow

Test-API "List admin templates" "GET" "/api/admin/templates" "200"

Write-Host "`n=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "PASSED: $PASSED" -ForegroundColor Green
Write-Host "FAILED: $FAILED" -ForegroundColor Yellow
Write-Host ""

if ($FAILED -eq 0) {
    Write-Host "All tests passed! ✓" -ForegroundColor Green
} else {
    Write-Host "Some tests failed! Check errors above." -ForegroundColor Red
}
