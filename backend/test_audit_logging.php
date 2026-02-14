<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Audit Logging Integration ===\n\n";

// Get user
$user = DB::table('users')->where('email', 'john@opf-cd.test')->first();
echo "User ID: {$user->id}\n\n";

// Check initial audit log count
$beforeCount = DB::table('audit_logs')->count();
echo "Initial audit log count: {$beforeCount}\n\n";

// Create a test project
$service = app('App\Services\ProjectManagementService');
$result = $service->createProject([
    'name' => 'Audit Test Project',
    'client' => 'Test Client',
    'contract_value' => 10000,
    'contract_currency' => 'USD',
    'start_date' => '2026-03-01',
    'end_date' => '2026-06-01',
    'status' => 'planned'
], $user->id, null);

echo "Create project result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Check audit log count after creation
$afterCount = DB::table('audit_logs')->count();
echo "Audit log count after creation: {$afterCount}\n";
echo "New audit logs created: " . ($afterCount - $beforeCount) . "\n\n";

// Get the latest audit log
if ($afterCount > $beforeCount) {
    $auditLog = DB::table('audit_logs')->orderBy('id', 'desc')->first();
    echo "Latest audit log:\n";
    echo "- ID: {$auditLog->id}\n";
    echo "- User ID: {$auditLog->user_id}\n";
    echo "- Action: {$auditLog->action}\n";
    echo "- Entity Type: {$auditLog->entity_type}\n";
    echo "- Entity ID: {$auditLog->entity_id}\n";
    echo "- Created At: {$auditLog->created_at}\n";
    echo "\n✅ Audit logging is working correctly!\n";
} else {
    echo "\n❌ Audit logging failed - no audit log was created!\n";
}

// Clean up test project
if ($result['success']) {
    DB::table('projects')->where('id', $result['project_id'])->delete();
    echo "\nTest project deleted.\n";
}
