#!/bin/bash

# Phase 5.4.5: Simple API Endpoint Testing (Bash)
# Tests key template endpoints

BASE_URL="http://127.0.0.1:8000"
PASSED=0
FAILED=0

echo "========== API ENDPOINT TESTING =========="
echo "Base URL: $BASE_URL"
echo ""

test_endpoint() {
    local method=$1
    local endpoint=$2
    local expected=$3
    
    echo -n "[$method] $endpoint ... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" -X "$method" "$BASE_URL$endpoint")
    
    if [ "$response" = "$expected" ]; then
        echo "✓ PASS ($response)"
        ((PASSED++))
    else
        echo "✗ FAIL (Expected $expected, got $response)"
        ((FAILED++))
    fi
}

echo "=== PUBLIC ENDPOINTS ==="
test_endpoint "GET" "/api/templates" "200"
test_endpoint "GET" "/api/templates/1" "200"
test_endpoint "GET" "/api/templates/1/preview" "200"
test_endpoint "GET" "/api/templates/99999" "404"
test_endpoint "GET" "/api/templates/1/tasks" "200"

echo ""
echo "=== ADMIN ENDPOINTS ==="
test_endpoint "GET" "/api/admin/templates" "200"
test_endpoint "GET" "/api/admin/templates/1" "200"

echo ""
echo "=== PROJECT ENDPOINTS ==="
test_endpoint "GET" "/api/opportunities" "200"
test_endpoint "GET" "/api/projects" "200"

echo ""
echo "=== SUMMARY ==="
echo "PASSED: $PASSED"
echo "FAILED: $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    echo "All tests passed! ✓"
else
    echo "Some tests failed."
fi
