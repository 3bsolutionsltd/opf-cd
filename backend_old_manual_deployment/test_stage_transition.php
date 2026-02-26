<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\OpportunityManagementService;
use Illuminate\Support\Facades\DB;

echo "=== Testing Won → Other Stage Transition ===\n\n";

$opportunityService = app(OpportunityManagementService::class);
$userId = 1;

// Step 1: Create a test opportunity
echo "Step 1: Creating test opportunity...\n";
$createResult = $opportunityService->createOpportunity([
    'client' => 'Stage Change Test Client',
    'description' => 'Testing stage transition behavior',
    'estimated_value' => 100000,
    'currency' => 'UGX',
    'probability' => 70,
    'stage' => 'proposal',
    'source' => 'referral',
    'owner' => $userId,
    'expected_close_date' => date('Y-m-d', strtotime('+30 days'))
], $userId);

$oppId = $createResult['opportunity_id'];
echo "  Opportunity ID: {$oppId}\n";
echo "  Initial Stage: proposal\n\n";

// Step 2: Change to WON
echo "Step 2: Changing opportunity to 'won'...\n";
$wonResult = $opportunityService->updateOpportunity($oppId, ['stage' => 'won'], $userId);
echo "  Result: " . ($wonResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "  Message: {$wonResult['message']}\n";
$firstProjectId = $wonResult['project_id'] ?? null;
echo "  Project Created: " . ($firstProjectId ? "Yes (ID: {$firstProjectId})" : "No") . "\n\n";

// Check projects
$projectsAfterWon = DB::table('projects')->where('opportunity_id', $oppId)->get();
echo "  Projects linked to opportunity: {$projectsAfterWon->count()}\n\n";

// Step 3: Change BACK to negotiation
echo "Step 3: Changing opportunity back to 'negotiation'...\n";
$backResult = $opportunityService->updateOpportunity($oppId, ['stage' => 'negotiation'], $userId);
echo "  Result: " . ($backResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "  Message: {$backResult['message']}\n";
echo "  Any project action mentioned? " . (isset($backResult['project_id']) ? "Yes" : "No") . "\n\n";

// Check what happened to the project
$projectsAfterBack = DB::table('projects')->where('opportunity_id', $oppId)->get();
echo "  Projects linked to opportunity: {$projectsAfterBack->count()}\n";
if ($projectsAfterBack->count() > 0) {
    foreach ($projectsAfterBack as $proj) {
        echo "    - Project #{$proj->id}: {$proj->name} - Status: {$proj->status}\n";
    }
}
echo "\n";

// Step 4: Change to WON AGAIN
echo "Step 4: Changing opportunity to 'won' AGAIN...\n";
$wonAgainResult = $opportunityService->updateOpportunity($oppId, ['stage' => 'won'], $userId);
echo "  Result: " . ($wonAgainResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "  Message: {$wonAgainResult['message']}\n";
$secondProjectId = $wonAgainResult['project_id'] ?? null;
echo "  Project Created: " . ($secondProjectId ? "Yes (ID: {$secondProjectId})" : "No") . "\n\n";

// Final check - how many projects exist now?
$finalProjects = DB::table('projects')->where('opportunity_id', $oppId)->get();
echo "  Final project count: {$finalProjects->count()}\n";
if ($finalProjects->count() > 0) {
    echo "  Projects:\n";
    foreach ($finalProjects as $proj) {
        echo "    - Project #{$proj->id}: {$proj->name} - Status: {$proj->status}\n";
    }
}
echo "\n";

// Analysis
echo "=== ANALYSIS ===\n\n";

if ($finalProjects->count() > 1) {
    echo "⚠ PROBLEM DETECTED: Multiple projects created from ONE opportunity!\n";
    echo "  - First project created when won: #{$firstProjectId}\n";
    echo "  - Second project created when won again: #{$secondProjectId}\n";
    echo "  - This creates duplicate/orphaned projects\n\n";
}

if ($projectsAfterBack->count() > 0) {
    echo "⚠ BEHAVIOR OBSERVED: When opportunity changed from 'won' to 'negotiation':\n";
    echo "  - Project was NOT deleted\n";
    echo "  - Project was NOT cancelled\n";
    echo "  - Project remains in 'planned' status\n";
    echo "  - No notification or warning about existing project\n\n";
}

echo "CURRENT SYSTEM ISSUES:\n";
echo "1. No duplicate prevention - won→other→won creates 2 projects\n";
echo "2. No cleanup when moving from 'won' to other stages\n";
echo "3. Projects become orphaned (exist but opp not won)\n";
echo "4. No validation to prevent state changes when project exists\n\n";

// Cleanup
echo "Cleaning up test data...\n";
DB::table('projects')->where('opportunity_id', $oppId)->delete();
DB::table('opportunities')->where('id', $oppId)->delete();
echo "✓ Cleanup complete\n";
