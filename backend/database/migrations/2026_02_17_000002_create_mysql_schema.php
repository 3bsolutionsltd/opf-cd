<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'project_manager', 'finance', 'sales', 'viewer'])->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('role');
            $table->index('is_active');
        });

        // Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('resource', 50);
            $table->string('action', 50);
            $table->string('description')->nullable();
            $table->timestamps();
            
            $table->index(['resource', 'action']);
        });

        // Create user_roles table
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id']);
        });

        // Create projects table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name');
            $table->text('description')->nullable();
            $table->decimal('contract_value_ksh', 15, 2);
            $table->decimal('contract_value_usd', 15, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['planned', 'active', 'paused', 'completed', 'cancelled'])->default('planned');
            $table->decimal('progress', 5, 2)->default(0);
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('status');
            $table->index('project_manager_id');
        });

        // Create tasks table
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->decimal('progress', 5, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $table->boolean('is_blocker')->default(false);
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('status');
            $table->index('assigned_to_id');
        });

        // Create payment_milestones table (table name used by the app)
        Schema::create('payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount_ksh', 15, 2);
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'invoiced', 'paid'])->default('pending');
            $table->date('invoiced_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('status');
        });
        
        // Create milestones alias view if it doesn't exist
        try {
            DB::statement('CREATE VIEW milestones AS SELECT * FROM payment_milestones');
        } catch (\Exception $e) {
            // View might already exist, ignore
        }

        // Create expenses table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount_ksh', 15, 2);
            $table->decimal('amount_usd', 15, 2)->nullable();
            $table->string('category', 50);
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_period', ['weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index('category');
            $table->index('status');
            $table->index('project_id');
        });

        // Create opportunities table
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name');
            $table->text('description')->nullable();
            $table->decimal('estimated_value_ksh', 15, 2);
            $table->decimal('estimated_value_usd', 15, 2)->nullable();
            $table->integer('probability')->default(50);
            $table->enum('stage', ['lead', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('lead');
            $table->date('expected_close_date')->nullable();
            $table->date('closed_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('stage');
            $table->index('owner_id');
        });

        // Create accounts table
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('account_number')->unique();
            $table->string('bank_name');
            $table->enum('currency', ['KSH', 'USD'])->default('KSH');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('currency');
            $table->index('is_active');
        });

        // Create cash_transactions table
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['inflow', 'outflow']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->date('transaction_date');
            $table->string('category', 50)->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_milestone_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            
            $table->index('account_id');
            $table->index('type');
            $table->index('transaction_date');
        });

        // Create exchange_rates table
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 10, 4);
            $table->date('effective_date');
            $table->timestamps();
            
            $table->index(['from_currency', 'to_currency', 'effective_date']);
        });

        // Create alerts table
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['payment_overdue', 'task_overdue', 'low_cash', 'project_at_risk'])->default('project_at_risk');
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index('type');
            $table->index('severity');
        });

        // Create audit_logs table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // Create password_reset_tokens table (Laravel default)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Create sessions table (Laravel default)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
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

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('expenses');
        DB::statement('DROP VIEW IF EXISTS milestones');
        Schema::dropIfExists('payment_milestones');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
