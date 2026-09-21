<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('opportunities') || Schema::hasColumn('opportunities', 'currency')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            $table->enum('currency', ['UGX', 'USD'])->default('UGX')->after('estimated_value');
            $table->index('currency', 'idx_opportunities_currency');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('opportunities') || !Schema::hasColumn('opportunities', 'currency')) {
            return;
        }

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex('idx_opportunities_currency');
            $table->dropColumn('currency');
        });
    }
};