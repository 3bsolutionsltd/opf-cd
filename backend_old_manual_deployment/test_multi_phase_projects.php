<?php

/**
 * Test: Multi-Phase Opportunity Project Creation
 * 
 * This test verifies:
 * 1. Auto-creation on first "won" stage change via service layer
 * 2. Duplicate prevention on won→other→won transitions
 * 3. Manual project creation from opportunities
 * 4. Multiple projects linking to same opportunity
 * 5. Project independence from opportunity stage changes
 * 
 * Run: php test_multi_phase_projects.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\OpportunityManagementService;
use App\Services\OpportunityProjectService;
use App\Services\AuditService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Multi-Phase Opportunity Project Creation Test ===\n\n";

// Initialize services
$auditService = new AuditService();
$opportunityProjectService = new OpportunityProjectService($auditService);
$opportunityService = new OpportunityManagementService($auditService, $opportunityProjectService);

// Test user ID (admin)
$userId = 1;

// Step 1: Create a test opportunity
echo "Step 1: Creating test opportunity...\n";
$createResult = $opportunityService->createOpportunity([
    'client' => 'Multi-Phase Test Client',
    'description' => 'Testing multi-phase project creation and duplicate prevention',
    'stage' => 'qualified',
    'estimated_value' => 100000,
    'probability' => 75,
    'currency' => 'USD',
    'source' => 'test',
    'expected_close_date' => '2024-06-30',
    'owner' => $userId
], $userId, null);

if (!$createResult['success']) {
    echo "✗ FAILED to create opportunity: {$createResult['message']}\n";
    exit(1);
}

$opportunityId = $createResult['opportunity_id'];
echo "✓ Opportunity created (ID: {$opportunityId})\n\n";

// Step 2: Change stage to "won" - should auto-create first project
echo "Step 2: Changing stage to 'won' (first time)...\n";
$updateResult = $opportunityService->updateOpportunity($opportunityId, [
    'client' => 'Multi-Phase Test Client',
    'description' => 'Testing multi-phase project creation and duplicate prevention',
    'stage' => 'won',
    'estimated_value' => 100000,
    'probability' => 75,
    'currency' => 'USD',
    'source' => 'test',
    'expected_close_date' => '2024-06-30',
    'owner' => $userId
], $userId, null);

if (!$updateResult['success']) {
    echo "✗ FAILED to update opportunity: {$updateResult['message']}\n";
    exit(1);
}

echo "  Update result: {$updateResult['message']}\n";

// Check for auto-created project
$projects = DB::table('projects')
    ->where('opportunity_id', $opportunityId)
    ->get();

if ($projects->count() === 1) {
    echo "✓ Project auto-created (ID: {$projects[0]->id})\n";
    echo "  - Name: {$projects[0]->name}\n";
    echo "  - Client: {$projects[0]->client}\n";
    echo "  - Contract Value: {$projects[0]->contract_value} {$projects[0]->contract_currency}\n";
    echo "  - Status: {$projects[0]->status}\n";
} else {
    echo "✗ FAILED: Expected 1 project, found {$projects->count()}\n";
    exit(1);
}
echo "\n";

// Step 3: Change stage to negotiation, then back to won - should NOT create duplicate
echo "Step 3: Testing duplicate prevention (won → negotiation → won)...\n";
$updateToNegotiation = $opportunityService->updateOpportunity($opportunityId, [
    'client' => 'Multi-Phase Test Client',
    'description' => 'Testing multi-phase project creation and duplicate prevention',
    'stage' => 'negotiation',
    'estimated_value' => 100000,
    'probability' => 75,
    'currency' => 'USD',
    'source' => 'test',
    'expected_close_date' => '2024-06-30',
    'owner' => $userId
], $userId, null);
echo "  - Changed to negotiation: {$updateToNegotiation['message']}\n";

sleep(1); // Brief pause for clarity

$updateBackToWon = $opportunityService->updateOpportunity($opportunityId, [
    'client' => 'Multi-Phase Test Client',
    'description' => 'Testing multi-phase project creation and duplicate prevention',
    'stage' => 'won',
    'estimated_value' => 100000,
    'probability' => 75,
    'currency' => 'USD',
    'source' => 'test',
    'expected_close_date' => '2024-06-30',
    'owner' => $userId
], $userId, null);
echo "  - Changed back to won: {$updateBackToWon['message']}\n";

$projectsAfter = DB::table('projects')
    ->where('opportunity_id', $opportunityId)
    ->get();

if ($projectsAfter->count() === 1) {
    echo "✓ PASSED: Duplicate prevention working - still 1 project\n";
} else {
    echo "✗ FAILED: Duplicate created! Found {$projectsAfter->count()} projects\n";
    echo "Project IDs: " . $projectsAfter->pluck('id')->implode(', ') . "\n";
    exit(1);
}
echo "\n";

// Step 4: Manually create second project (Phase 2)
echo "Step 4: Manually creating Phase 2 project...\n";
$phase2Result = $opportunityService->createProjectFromOpportunity($opportunityId, [
    'name' => 'Multi-Phase Test Client - Phase 2 Enhancement',
    'contract_value' => 50000,
    'contract_currency' => 'USD',
    'start_date' => '2024-07-01',
    'end_date' => '2024-09-30',
    'status' => 'planned',
    'project_lead_id' => null
], $userId, null);

if (!$phase2Result['success']) {
    echo "✗ FAILED to create Phase 2: {$phase2Result['message']}\n";
    exit(1);
}

$phase2Id = $phase2Result['project_id'];
echo "✓ Phase 2 project created (ID: {$phase2Id})\n\n";

// Step 5: Manually create third project (Phase 3 - Maintenance)
echo "Step 5: Manually creating Phase 3 project...\n";
$phase3Result = $opportunityService->createProjectFromOpportunity($opportunityId, [
    'name' => 'Multi-Phase Test Client - Phase 3 Maintenance',
    'contract_value' => 30000,
    'contract_currency' => 'USD',
    'start_date' => '2024-10-01',
    'end_date' => null,  // Ongoing maintenance
    'status' => 'planned',
    'project_lead_id' => null
], $userId, null);

if (!$phase3Result['success']) {
    echo "✗ FAILED to create Phase 3: {$phase3Result['message']}\n";
    exit(1);
}

$phase3Id = $phase3Result['project_id'];
echo "✓ Phase 3 project created (ID: {$phase3Id})\n\n";

// Step 6: Verify all projects are linked correctly
echo "Step 6: Verifying all projects linked to opportunity...\n";
$allProjects = DB::table('projects')
    ->where('opportunity_id', $opportunityId)
    ->orderBy('created_at', 'asc')
    ->get();

if ($allProjects->count() === 3) {
    echo "✓ PASSED: All 3 projects linked correctly\n";
    foreach ($allProjects as $index => $project) {
        echo "  Phase " . ($index + 1) . " (ID: {$project->id}): {$project->name}\n";
        echo "    Contract: {$project->contract_value} {$project->contract_currency}\n";
        echo "    Status: {$project->status}\n";
        echo "    Dates: {$project->start_date} → " . ($project->end_date ?? 'ongoing') . "\n";
    }
} else {
    echo "✗ FAILED: Expected 3 projects, found {$allProjects->count()}\n";
    exit(1);
}
echo "\n";

// Step 7: Test opportunity stage change doesn't affect projects
echo "Step 7: Testing project independence (changing opportunity to 'lost')...\n";
$opportunityService->updateOpportunity($opportunityId, [
    'client' => 'Multi-Phase Test Client',
    'description' => 'Testing multi-phase project creation and duplicate prevention',
    'stage' => 'lost',
    'estimated_value' => 100000,
    'probability' => 75,
    'currency' => 'USD',
    'source' => 'test',
    'expected_close_date' => '2024-06-30',
    'owner' => $userId
], $userId, null);

$projectsStillExist = DB::table('projects')
    ->where('opportunity_id', $opportunityId)
    ->get();

if ($projectsStillExist->count() === 3) {
    echo "✓ PASSED: All 3 projects still exist after opportunity marked as lost\n";
    echo "  - Projects have independent lifecycle\n";
} else {
    echo "✗ FAILED: Projects affected by opportunity stage change\n";
    exit(1);
}
echo "\n";

// Step 8: Cleanup
echo "Step 8: Cleaning up test data...\n";
DB::table('projects')->where('opportunity_id', $opportunityId)->delete();
DB::table('opportunities')->where('id', $opportunityId)->delete();
echo "✓ Test data cleaned up\n\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "✓ All tests passed!\n\n";
echo "Verified features:\n";
echo "  1. Auto-creation on first 'won' stage change\n";
echo "  2. Duplicate prevention on won→other→won transitions\n";
echo "  3. Manual project creation (multiple phases)\n";
echo "  4. All projects correctly linked via opportunity_id\n";
echo "  5. Projects remain independent of opportunity stage changes\n";
echo "\n";
echo "Implementation complete!\n";
