<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CORRECTED MySQL Schema Migration
 * 
 * This migration properly converts the PostgreSQL schema to MySQL.
 * It drops and recreates all tables with the EXACT structure from PostgreSQL.
 */
return new class extends Migration
{
    /**
     * Run the migrations for MySQL database.
     */
    public function up(): void
    {
        // Skip if not MySQL
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop all existing tables in reverse dependency order
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('expenses');
        DB::statement('DROP VIEW IF EXISTS milestones');
        Schema::dropIfExists('payment_milestones');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('users');
        
        //=================================================================
        // 1. USERS TABLE
        //=================================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', ['admin', 'project_manager', 'finance', 'sales', 'viewer']);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
            
            $table->index('email');
            $table->index('role');
            $table->index('is_active');
            $table->index('created_at');
        });

        //=================================================================
        // 2. PROJECTS TABLE
        //=================================================================
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('client', 255);
            $table->decimal('contract_value', 15, 2);
            $table->enum('contract_currency', ['UGX', 'USD']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['planned', 'active', 'on_hold', 'completed', 'cancelled'])->default('planned');
            $table->foreignId('project_lead_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            
            $table->index('status');
            $table->index('project_lead_id');
            $table->index('client');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('created_at');
        });
        
        // Add check constraints
        DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_contract_value CHECK (contract_value >= 0)');
        DB::statement('ALTER TABLE projects ADD CONSTRAINT chk_projects_valid_date_range CHECK (end_date >= start_date)');

        //=================================================================
        // 3. TASKS TABLE
        //=================================================================
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->string('category', 100)->nullable();
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('progress', 5, 2)->default(0);
            $table->enum('status', ['todo', 'wip', 'blocked', 'done'])->default('todo');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestampsTz();
            
            $table->index('project_id');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('due_date');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT chk_tasks_weight CHECK (weight >= 0 AND weight <= 100)');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT chk_tasks_progress CHECK (progress >= 0 AND progress <= 100)');
        DB::statement('ALTER TABLE tasks ADD CONSTRAINT chk_tasks_valid_date_range CHECK (due_date IS NULL OR start_date IS NULL OR due_date >= start_date)');

        //=================================================================
        // 4. PAYMENT_MILESTONES TABLE
        //=================================================================
        Schema::create('payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['UGX', 'USD']);
            $table->enum('status', ['pending', 'invoiced', 'paid'])->default('pending');
            $table->date('due_date');
            $table->timestampsTz();
            
            $table->index('project_id');
            $table->index('status');
            $table->index('due_date');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE payment_milestones ADD CONSTRAINT chk_payment_milestones_amount CHECK (amount >= 0)');
        
        // Create milestones view
        DB::statement('CREATE VIEW milestones AS SELECT * FROM payment_milestones');

        //=================================================================
        // 5. EXPENSES TABLE
        //=================================================================
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('category', 100);
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['UGX', 'USD']);
            $table->enum('type', ['recurring', 'one_off']);
            $table->enum('frequency', ['monthly', 'quarterly', 'annual'])->nullable();
            $table->enum('status', ['due', 'paid'])->default('due');
            $table->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('due_date');
            $table->timestampsTz();
            
            $table->index('type');
            $table->index('status');
            $table->index('project_id');
            $table->index('due_date');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE expenses ADD CONSTRAINT chk_expenses_amount CHECK (amount >= 0)');

        //=================================================================
        // 6. ACCOUNTS TABLE
        //=================================================================
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('type', ['bank', 'mobile_money', 'cash']);
            $table->enum('currency', ['UGX', 'USD']);
            $table->decimal('opening_balance', 15, 2);
            $table->timestampsTz();
            
            $table->index('type');
            $table->index('currency');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE accounts ADD CONSTRAINT chk_accounts_opening_balance CHECK (opening_balance >= 0)');

        //=================================================================
        // 7. CASH_TRANSACTIONS TABLE
        //=================================================================
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['inflow', 'outflow']);
            $table->decimal('amount', 15, 2);
            $table->enum('currency', ['UGX', 'USD']);
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->date('transaction_date');
            $table->timestampTz('created_at')->useCurrent();
            
            $table->index('account_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index(['source_type', 'source_id'], 'idx_cash_transactions_source');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE cash_transactions ADD CONSTRAINT chk_cash_transactions_amount CHECK (amount > 0)');

        //=================================================================
        // 8. OPPORTUNITIES TABLE
        //=================================================================
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('client', 255);
            $table->string('description', 255);
            $table->decimal('estimated_value', 15, 2);
            $table->decimal('probability', 5, 2);
            $table->enum('stage', ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('lead');
            $table->string('source', 100);
            $table->foreignId('owner')->constrained('users')->restrictOnDelete();
            $table->date('expected_close_date');
            $table->timestampsTz();
            
            $table->index('stage');
            $table->index('owner');
            $table->index('expected_close_date');
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE opportunities ADD CONSTRAINT chk_opportunities_estimated_value CHECK (estimated_value >= 0)');
        DB::statement('ALTER TABLE opportunities ADD CONSTRAINT chk_opportunities_probability CHECK (probability >= 0 AND probability <= 100)');

        //=================================================================
        // 9. EXCHANGE_RATES TABLE
        //=================================================================
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->enum('base_currency', ['UGX', 'USD']);
            $table->enum('quote_currency', ['UGX', 'USD']);
            $table->decimal('rate', 10, 6);
            $table->date('effective_date')->unique();
            $table->timestampTz('created_at')->useCurrent();
            
            $table->index('created_at');
        });
        
        DB::statement('ALTER TABLE exchange_rates ADD CONSTRAINT chk_exchange_rates_base CHECK (base_currency = \'UGX\')');
        DB::statement('ALTER TABLE exchange_rates ADD CONSTRAINT chk_exchange_rates_quote CHECK (quote_currency = \'USD\')');
        DB::statement('ALTER TABLE exchange_rates ADD CONSTRAINT chk_exchange_rates_rate CHECK (rate > 0)');

        //=================================================================
        // 10. ROLES TABLE
        //=================================================================
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestampsTz();
            
            $table->index('name');
        });

        //=================================================================
        // 11. USER_ROLES TABLE
        //=================================================================
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            
            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');
        });

        //=================================================================
        // 12. PERMISSIONS TABLE
        //=================================================================
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->enum('resource', ['projects', 'tasks', 'milestones', 'expenses', 'accounts', 'cash_transactions', 'opportunities', 'users', 'roles', 'permissions', 'dashboards']);
            $table->enum('action', ['view', 'create', 'edit', 'delete', 'manage']);
            $table->timestampTz('created_at')->useCurrent();
            
            $table->unique(['role_id', 'resource', 'action']);
            $table->index('role_id');
            $table->index('resource');
            $table->index('action');
        });

        //=================================================================
        // 13. ALERTS TABLE
        //=================================================================
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['project_behind_schedule', 'payment_gap_breach', 'low_cash_runway', 'expense_overdue', 'opportunity_closing_soon']);
            $table->enum('severity', ['info', 'warning', 'critical']);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->text('message');
            $table->boolean('is_dismissed')->default(false);
            $table->timestampTz('dismissed_at')->nullable();
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            
            $table->index('type');
            $table->index('severity');
            $table->index(['entity_type', 'entity_id'], 'idx_alerts_entity');
            $table->index('is_dismissed');
            $table->index('created_at');
        });

        //=================================================================
        // 14. AUDIT_LOGS TABLE
        //=================================================================
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('action', ['create', 'update', 'delete']);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('user_id', 'idx_audit_user');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
            $table->index('action', 'idx_audit_action');
            $table->index('created_at', 'idx_audit_created');
            $table->index(['entity_type', 'entity_id', 'created_at'], 'idx_audit_entity_time');
        });

        // Laravel default tables (users, cache, jobs, password_reset_tokens, sessions)
        // are handled by Laravel's default migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip if not MySQL
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop only the tables we created (not Laravel default tables)
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('expenses');
        DB::statement('DROP VIEW IF EXISTS milestones');
        Schema::dropIfExists('payment_milestones');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('users');
    }
};
