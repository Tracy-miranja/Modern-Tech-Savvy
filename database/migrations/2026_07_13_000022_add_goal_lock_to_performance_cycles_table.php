<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Phase 03 of the OKR strategy: once a cycle starts, its goals lock -
     * new objectives/key results can no longer be added, only progress
     * against what was already committed. Default ON per product guidance.
     */
    public function up(): void
    {
        if (Schema::hasColumn('performance_cycles', 'lock_goals_on_start')) {
            return;
        }

        Schema::table('performance_cycles', function (Blueprint $table) {
            $table->boolean('lock_goals_on_start')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('performance_cycles', 'lock_goals_on_start')) {
            return;
        }

        Schema::table('performance_cycles', function (Blueprint $table) {
            $table->dropColumn('lock_goals_on_start');
        });
    }
};
