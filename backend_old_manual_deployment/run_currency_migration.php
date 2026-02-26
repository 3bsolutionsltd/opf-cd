<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Running migration: Add currency to opportunities\n";
echo "================================================\n\n";

try {
    // Execute each SQL statement separately
    echo "1. Adding currency column...\n";
    DB::statement("ALTER TABLE opportunities 
                   ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'UGX' 
                   CHECK (currency IN ('USD', 'UGX'))");
    echo "   ✓ Column added\n\n";
    
    echo "2. Creating index...\n";
    DB::statement("CREATE INDEX idx_opportunities_currency ON opportunities(currency)");
    echo "   ✓ Index created\n\n";
    
    echo "3. Adding column comment...\n";
    DB::statement("COMMENT ON COLUMN opportunities.currency IS 'Currency for estimated value (USD or UGX)'");
    echo "   ✓ Comment added\n\n";
    
    echo "✓ Migration completed successfully!\n\n";
    
    // Verify the column was added
    $result = DB::select("SELECT column_name, data_type, column_default 
                          FROM information_schema.columns 
                          WHERE table_name = 'opportunities' AND column_name = 'currency'");
    
    if (!empty($result)) {
        echo "✓ Currency column verified in opportunities table\n";
        foreach ($result as $col) {
            echo "   - Column: {$col->column_name}\n";
            echo "   - Type: {$col->data_type}\n";
            echo "   - Default: {$col->column_default}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "\nColumn may already exist. Verification:\n";
        $result = DB::select("SELECT column_name FROM information_schema.columns 
                              WHERE table_name = 'opportunities' AND column_name = 'currency'");
        if (!empty($result)) {
            echo "✓ Currency column exists\n";
        }
    }
    exit(1);
}
