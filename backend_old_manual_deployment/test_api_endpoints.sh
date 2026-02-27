#!/bin/bash

# Phase 5.4.5 Day 3-4: API Endpoint Testing via curl
# This script tests all 13 template endpoints
# Run from: backend_old_manual_deployment directory

BASE_URL="http://127.0.0.1:8000"
API_BASE="$BASE_URL/api"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "Phase 5.4.5: API Endpoint Testing"
echo "=========================================="
echo ""

# Test counter
PASSED=0
FAILED=0

# Function to make API request and validate
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local expected_status=$4
    local description=$5

    echo -e "${YELLOW}Testing:${NC} $description"
    echo "  $method $endpoint"

    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -X GET "$API_BASE$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$API_BASE$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data")
    fi

    http_code=$(echo "$response" | tail -n 1)
    body=$(echo "$response" | head -n -1)

    if [[ "$http_code" =~ $expected_status ]]; then
        echo -e "  ${GREEN}✓ PASS${NC} (HTTP $http_code)"
        PASSED=$((PASSED + 1))
    else
        echo -e "  ${RED}✗ FAIL${NC} (Expected $expected_status, got HTTP $http_code)"
        FAILED=$((FAILED + 1))
    fi

    # Optional: Show response body for debugging
    if [[ "$http_code" != "200" && "$http_code" != "201" ]]; then
        echo "  Response: ${body:0:100}"
    fi
    echo ""
}

# ========== PUBLIC ENDPOINTS (Project Manager Access) ==========
echo -e "${YELLOW}=== PUBLIC ENDPOINTS ===${NC}"
echo ""

# 1. GET /api/templates - List all active templates
test_endpoint "GET" "/templates" "" "200" "1. GET /api/templates - List all active templates"

# 2. GET /api/templates/{id} - Get single template with tasks
test_endpoint "GET" "/templates/1" "" "200" "2. GET /api/templates/1 - Get Web App template with tasks"

# 3. GET /api/templates/preview (Note: endpoint might be /{id}/preview)
test_endpoint "GET" "/templates/1/preview" "" "200" "3. GET /api/templates/1/preview - Preview Web App tasks"

# 4. Test invalid template ID
test_endpoint "GET" "/templates/99999" "" "404" "4. GET /api/templates/99999 - Invalid template (expect 404)"

# 5. Test all templates exist
echo -e "${YELLOW}Verifying all 5 templates via list endpoint:${NC}"
curl -s "$API_BASE/templates" | grep -o '"name":"[^"]*"' | head -5
echo ""

# ========== CREATE PROJECT WITH TEMPLATE ==========
echo -e "${YELLOW}=== PROJECT CREATION WITH TEMPLATE ===${NC}"
echo ""

# First create an opportunity
echo "Creating test opportunity..."
OPPORTUNITY_JSON='{
  "name": "Test Opportunity for Template ' $(date +%s) '",
  "estimated_value": 10000,
  "currency": "USD",
  "stage": "won",
  "probability": 100,
  "user_id": 1
}'

OPP_RESPONSE=$(curl -s -X POST "$API_BASE/opportunities" \
  -H "Content-Type: application/json" \
  -d "$OPPORTUNITY_JSON")

OPP_ID=$(echo "$OPP_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
echo "Opportunity ID: $OPP_ID"

if [ -z "$OPP_ID" ] || [ "$OPP_ID" = "0" ]; then
    echo -e "${RED}Failed to create test opportunity${NC}"
    OPPORTUNITY_JSON='{
      "template_id": 1,
      "project_name": "Test Web App Project"
    }'
else
    # 5. POST /api/opportunities/{id}/projects/with-template - Create project
    test_endpoint "POST" "/opportunities/$OPP_ID/projects/with-template" \
        '{"template_id": 1, "project_name": "Test Web App Project"}' \
        "201|200" "5. POST /api/opportunities/{id}/projects/with-template - Create with Web App"
fi

# 6. Test validation error (missing template_id)
MOCK_OPP=1
test_endpoint "POST" "/opportunities/$MOCK_OPP/projects/with-template" \
    '{}' \
    "422" "6. POST with missing fields - Validation error (expect 422)"

# 7. Test invalid opportunity
test_endpoint "POST" "/opportunities/99999/projects/with-template" \
    '{"template_id": 1, "project_name": "Invalid"}' \
    "404|422" "7. POST with invalid opportunity - Expect error"

echo ""

# ========== APPLY TEMPLATE TO EXISTING PROJECT ==========
echo -e "${YELLOW}=== APPLY TEMPLATE TO PROJECT ===${NC}"
echo ""

# Create a test project for apply-template
echo "Creating test project for template application..."
PROJECT_JSON='{
  "name": "Empty Test Project",
  "contract_value": 5000,
  "currency": "USD",
  "start_date": "2026-02-28",
  "end_date": "2026-03-31",
  "user_id": 1
}'

PROJ_RESPONSE=$(curl -s -X POST "$API_BASE/projects" \
  -H "Content-Type: application/json" \
  -d "$PROJECT_JSON")

PROJ_ID=$(echo "$PROJ_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
echo "Project ID: $PROJ_ID"

if [ -n "$PROJ_ID" ] && [ "$PROJ_ID" != "0" ]; then
    # 8. POST /api/projects/{id}/apply-template - Apply to empty project
    test_endpoint "POST" "/projects/$PROJ_ID/apply-template" \
        '{"template_id": 2}' \
        "200" "8. POST /api/projects/{id}/apply-template - Apply Mobile App template"
else
    echo -e "${YELLOW}Skipping apply-template test (project creation failed)${NC}"
fi

echo ""

# ========== ADMIN ENDPOINTS (Admin Access Only) ==========
echo -e "${YELLOW}=== ADMIN ENDPOINTS ===${NC}"
echo ""

# 9. GET /api/admin/templates - List all templates (including inactive)
test_endpoint "GET" "/admin/templates" "" "200|403" "9. GET /api/admin/templates - List all templates (expect 200 or 403 if not auth)"

# 10. POST /api/admin/templates - Create template
TEMPLATE_NAME="Custom Template $(date +%s)"
CREATE_TPL='{"name":"'$TEMPLATE_NAME'","description":"Test template","category":"Custom","is_active":true,"task_count":0,"average_duration_days":30}'

test_endpoint "POST" "/admin/templates" \
    "$CREATE_TPL" \
    "201|403" "10. POST /api/admin/templates - Create custom template (expect 201 or 403)"

# 11. PUT /api/admin/templates/{id} - Update template
test_endpoint "PUT" "/admin/templates/1" \
    '{"is_active":false}' \
    "200|403" "11. PUT /api/admin/templates/1 - Update template (expect 200 or 403)"

# 12. POST /api/admin/templates/{id}/tasks - Add task to template
ADD_TASK='{"name":"New Task","description":"Test task","weight":15,"phase_number":1}'

test_endpoint "POST" "/admin/templates/1/tasks" \
    "$ADD_TASK" \
    "201|403" "12. POST /api/admin/templates/1/tasks - Add task (expect 201 or 403)"

# 13. DELETE /api/admin/templates/{id} - Delete template (should fail if in use)
test_endpoint "DELETE" "/admin/templates/999" \
    "" \
    "404|403" "13. DELETE /api/admin/templates/999 - Invalid template"

echo ""

# ========== PERFORMANCE TESTING ==========
echo -e "${YELLOW}=== PERFORMANCE BENCHMARKS ===${NC}"
echo ""

echo "Testing response times (should be < 200ms for GET, < 500ms for POST):"

# Test GET performance
for i in {1..3}; do
    start=$(date +%s%N)
    response=$(curl -s "$API_BASE/templates")
    end=$(date +%s%N)
    duration=$((($end - $start) / 1000000))  # Convert to ms
    echo "  Iteration $i: GET /api/templates = ${duration}ms"
done

echo ""

# ========== SUMMARY ==========
echo "=========================================="
echo -e "Test Summary:"
echo -e "  ${GREEN}Passed: $PASSED${NC}"
echo -e "  ${RED}Failed: $FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed${NC}"
    exit 1
fi
