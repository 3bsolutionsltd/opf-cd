<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create project_templates table for template management
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->string('category', 100); // Web App, Mobile App, E-Commerce, Integration, Maintenance
            $table->boolean('is_active')->default(true)->nullable(false);
            $table->integer('task_count')->default(0)->nullable(false); // Denormalized for quick display
            $table->integer('average_duration_days')->nullable(); // Typical project duration
            $table->timestamps(); // Creates created_at and updated_at
            
            // Indexes
            $table->index('category', 'idx_templates_category');
            $table->index('is_active', 'idx_templates_active');
            $table->index('created_at', 'idx_templates_created_at');
        });

        // Create project_template_tasks table for individual tasks in templates
        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')
                ->constrained('project_templates')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(0); // Percentage weight - NUMERIC(5,2) equivalent
            $table->integer('phase_number'); // Display order
            $table->integer('estimated_duration_days')->nullable(); // Typical duration for this phase
            $table->string('dependencies', 255)->nullable(); // Comma-separated phase numbers this depends on
            $table->timestamps(); // Creates created_at and updated_at
            
            // Note: Weight constraint (0-100) is enforced at application level
            // Laravel's check() method is not consistently supported across databases
            
            // Indexes
            $table->index('project_template_id', 'idx_template_tasks_template');
            $table->index('phase_number', 'idx_template_tasks_phase');
            $table->index('created_at', 'idx_template_tasks_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_template_tasks');
        Schema::dropIfExists('project_templates');
    }
};
