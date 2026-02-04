<?php
$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=opf_cd', 'postgres', 'password123');

$migrations = [
    '001_create_users_table.sql',
    '002_create_projects_table.sql',
    '003_create_tasks_table.sql',
    '004_create_payment_milestones_table.sql',
    '005_create_expenses_table.sql',
    '006_create_accounts_table.sql',
    '007_create_cash_transactions_table.sql',
    '008_create_opportunities_table.sql',
    '009_create_exchange_rates_table.sql',
];

foreach ($migrations as $migration) {
    $file = __DIR__ . '/database/migrations/' . $migration;
    if (!file_exists($file)) {
        echo "File not found: $migration\n";
        continue;
    }
    
    $sql = file_get_contents($file);
    try {
        $pdo->exec($sql);
        echo "SUCCESS: $migration\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "SKIPPED (already exists): $migration\n";
        } else {
            echo "ERROR in $migration: " . $e->getMessage() . "\n";
        }
    }
}
