<?php

/**
 * Migration: create_business_goals_table
 * Version: 1.0
 * Date: 2026-02-28
 * Author: OPF-CD System
 *
 * Creates business_goals table for storing business targets and tracking progress.
 * Part of Section 1: Business Health KPIs & KPAs (Phase 5.2)
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-002
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_goals', function (Blueprint $table) {
            $table->id();
            $table->string('goal_type', 100)->comment('Type of business goal e.g. revenue_target, conversion_rate_target');
            $table->string('period', 50)->comment('Period the goal applies to e.g. Q1_2026, 2026-02');
            $table->decimal('target_value', 15, 2)->comment('Target value to achieve NUMERIC(15,2)');
            $table->decimal('current_value', 15, 2)->default(0)->comment('Current achieved value NUMERIC(15,2)');
            $table->string('status', 20)->default('active')->comment('Goal status: active, on_track, at_risk, behind, or achieved');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('Progress as a percentage 0-100 NUMERIC(5,2)');
            $table->json('prescriptive_actions')->nullable()->comment('JSON array of recommended actions to achieve the goal');
            $table->unsignedBigInteger('created_by')->nullable()->comment('ID of user who created the goal');
            $table->timestampsTz();

            // Foreign key
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index(['goal_type', 'period'], 'idx_business_goals_type_period');
            $table->index('status', 'idx_business_goals_status');
            $table->index('created_at', 'idx_business_goals_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_goals');
    }
};
