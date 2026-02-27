<?php
/**
 * Test Diagnostic Script
 * Checks what's failing in ProjectTemplateServiceTest
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Diagnostics ===\n\n";

// Check 1: Can we instantiate the service?
echo "1. Testing ProjectTemplateService instantiation...\n";
try {
    $service = new \App\Services\ProjectTemplateService();
    echo "   ✓ Service instantiated successfully\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check 2: Database connection
echo "\n2. Testing database connection...\n";
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "   ✓ Database connected\n";
    echo "   Database: " . config('database.default') . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Check 3: Check if templates table exists
echo "\n3. Checking if project_templates table exists...\n";
try {
    $tables = \Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='project_templates'");
    if (count($tables) > 0) {
        echo "   ✓ project_templates table exists\n";
    } else {
        echo "   ✗ project_templates table does NOT exist\n";
        echo "   Run migrations first: php artisan migrate\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Check 4: Check template count
echo "\n4. Checking template data...\n";
try {
    $count = \Illuminate\Support\Facades\DB::table('project_templates')->count();
    echo "   Templates in database: $count\n";
    if ($count === 0) {
        echo "   ✗ No templates found. Run seeder: php artisan db:seed --class=ProjectTemplateSeeder\n";
    } else {
        echo "   ✓ Templates found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Check 5: Try calling the service method
echo "\n5. Testing getAllActiveTemplates() method...\n";
try {
    $templates = $service->getAllActiveTemplates();
    echo "   ✓ Method executed successfully\n";
    echo "   Returned: " . $templates->count() . " templates\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
