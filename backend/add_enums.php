<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$enums = ['alerts', 'audit_logs', 'reports'];

foreach ($enums as $enum) {
    try {
        DB::statement("ALTER TYPE resource_type ADD VALUE '$enum'");
        echo "Added '$enum' to resource_type enum\n";
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            echo "'$enum' already exists in enum\n";
        } else {
            echo "Error adding '$enum': " . $e->getMessage() . "\n";
        }
    }
}

echo "\nDone!\n";
