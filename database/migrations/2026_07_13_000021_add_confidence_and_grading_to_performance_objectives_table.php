<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Phase 02 of the OKR strategy: an auto-flagged confidence indicator
     * (recomputed on every progress update) and the 0.0-1.0 stretch-goal
     * grade (computed once, when the cycle closes).
     */
    public function up(): void
    {
        if (Schema::hasColumn('performance_objectives', 'confidence')) {
            return;
        }

        Schema::table('performance_objectives', function (Blueprint $table) {
            $table->enum('confidence', ['on_track', 'at_risk', 'critical'])
                ->default('on_track')
                ->after('status');
            $table->decimal('final_score', 3, 2)->nullable()->after('confidence');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('performance_objectives', 'confidence')) {
            return;
        }

        Schema::table('performance_objectives', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'final_score']);
        });
    }
};
