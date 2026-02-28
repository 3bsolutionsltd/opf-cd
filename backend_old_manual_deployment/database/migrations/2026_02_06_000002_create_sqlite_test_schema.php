<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for SQLite database.
     * 
     * This migration loads the SQLite schema for testing.
     * It creates all necessary tables for the OPF-CD system.
     */
    public function up(): void
    {
        // Only run this migration for SQLite (testing)
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }
        
        // Drop Laravel's default tables first (they conflict with our schema)
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('users');
        
        $sqlPath = database_path('migrations/sqlite_schema.sql');
        
        if (!file_exists($sqlPath)) {
            throw new \RuntimeException("SQLite schema file not found");
        }
        
        $sql = file_get_contents($sqlPath);
        
        // Remove comments
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
                // Skip errors for tables that already exist
                if (!str_contains($e->getMessage(), 'already exists')) {
                    throw new \RuntimeException(
                        "Failed executing SQLite statement: " . substr($statement, 0, 100) . "...\n" .
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
        // Only run for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }
        
        // Drop all tables
        $tables = [
            'cash_transactions',
            'accounts',
            'expenses',
            'payment_milestones',
            'tasks',
            'projects',
            'opportunities',
            'exchange_rates',
            'alerts',
            'audit_logs',
            'permissions',
            'user_roles',
            'roles',
            'users',
        ];
        
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
