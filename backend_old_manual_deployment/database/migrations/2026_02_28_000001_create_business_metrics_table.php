<?php

/**
 * Migration: create_business_metrics_table
 * Version: 1.0
 * Date: 2026-02-28
 * Author: OPF-CD System
 *
 * Creates business_metrics table for storing calculated KPIs with historical tracking.
 * Part of Section 1: Business Health KPIs & KPAs (Phase 5.2)
 *
 * Source: docs/STRATEGIC_VISION_TASK_BREAKDOWN.md - Task BH-001
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
        Schema::create('business_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type', 100)->comment('Type of KPI metric e.g. opportunity_conversion_rate, sales_velocity');
            $table->string('period', 50)->comment('Period identifier e.g. Q1_2026, 2026-02, weekly_2026-W08');
            $table->decimal('metric_value', 15, 2)->comment('Calculated metric value NUMERIC(15,2)');
            $table->decimal('target_value', 15, 2)->nullable()->comment('Optional target/benchmark value for comparison');
            $table->string('status', 20)->comment('KPI status: on_track, at_risk, or behind');
            $table->json('entity_filter')->nullable()->comment('Optional JSON filter applied during calculation e.g. {"currency":"USD"}');
            $table->timestampTz('calculated_at')->comment('Timestamp when the metric was last calculated');
            $table->timestampTz('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->comment('Record creation timestamp');

            // Indexes
            $table->index(['metric_type', 'period'], 'idx_business_metrics_type_period');
            $table->index('calculated_at', 'idx_business_metrics_calculated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_metrics');
    }
};
