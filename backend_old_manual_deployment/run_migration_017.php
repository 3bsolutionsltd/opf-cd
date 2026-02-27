<?php
/**
 * Run SQL Migration: 017_create_project_templates_table.sql
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Running Migration 017 ===\n\n";

$migrationFile = __DIR__ . '/database/migrations/017_create_project_templates_table.sql';

if (!file_exists($migrationFile)) {
    echo "✗ Migration file not found: $migrationFile\n";
    exit(1);
}

$sql = file_get_contents($migrationFile);

try {
    // Execute the SQL
    \Illuminate\Support\Facades\DB::unprepared($sql);
    echo "✓ Migration 017 executed successfully\n";
    echo "✓ Created tables: project_templates, project_template_tasks\n";
} catch (Exception $e) {
    echo "✗ Error executing migration: " . $e->getMessage() . "\n";
    exit(1);
}

// Verify tables were created
try {
    $tables = \Illuminate\Support\Facades\DB::select("
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'public' 
        AND tablename IN ('project_templates', 'project_template_tasks')
    ");
    
    echo "\nVerification:\n";
    foreach ($tables as $table) {
        echo "  ✓ Table exists: {$table->tablename}\n";
    }
} catch (Exception $e) {
    echo "✗ Error verifying tables: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
