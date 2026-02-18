<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for PostgreSQL database.
     * 
     * This migration loads the PostgreSQL schema files in order.
     * It creates all necessary tables for the OPF-CD system.
     */
    public function up(): void
    {
        // Skip this migration if not using PostgreSQL
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        
        // Drop Laravel's default tables first (they conflict with our schema)
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        
        // Drop all custom enum types if they exist
        $customTypes = DB::select("SELECT typname FROM pg_type WHERE typtype = 'e' AND typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')");
        foreach ($customTypes as $type) {
            DB::statement("DROP TYPE IF EXISTS {$type->typname} CASCADE");
        }
        
        // List of SQL migration files in order
        $sqlFiles = [
            '001_create_users_table.sql',
            '002_create_projects_table.sql',
            '003_create_tasks_table.sql',
            '004_create_payment_milestones_table.sql',
            '005_create_expenses_table.sql',
            '006_create_accounts_table.sql',
            '007_create_cash_transactions_table.sql',
            '008_create_opportunities_table.sql',
            '009_create_exchange_rates_table.sql',
            '009_create_roles_table.sql',
            '010_create_user_roles_table.sql',
            '011_create_permissions_table.sql',
            '012_create_alerts_table.sql',
            '013_create_audit_logs_table.sql',
        ];
        
        foreach ($sqlFiles as $filename) {
            $sqlPath = database_path("migrations/{$filename}");
            
            if (!file_exists($sqlPath)) {
                throw new \RuntimeException("Migration file not found: {$filename}");
            }
            
            $sql = file_get_contents($sqlPath);
            
            // Remove single-line comments
            $sql = preg_replace('/--.*$/m', '', $sql);
            
            // Split by semicolon
            $statements = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                
                if (empty($statement)) {
                    continue;
                }
                
                if (!str_ends_with($statement, ';')) {
                    $statement .= ';';
                }
                
                try {
                    DB::statement($statement);
                } catch (\Exception $e) {
                    throw new \RuntimeException(
                        "Failed in {$filename}: " . substr($statement, 0, 100) . "...\n" .
                        "Error: " . $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip this migration if not using PostgreSQL
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        
        // Drop all tables
        $tables = [
            'permissions',
            'user_roles',
            'roles',
            'exchange_rates',
            'opportunities',
            'cash_transactions',
            'accounts',
            'expenses',
            'payment_milestones',
            'tasks',
            'projects',
            'users',
        ];
        
        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS {$table}");
        }
    }
};
