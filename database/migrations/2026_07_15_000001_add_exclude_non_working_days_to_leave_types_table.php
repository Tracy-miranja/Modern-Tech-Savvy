<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Moves "exclude the business's non-working days" from an
     * unconditional, business-wide rule into a per-leave-type opt-in -
     * mirrors the existing exclude_public_holidays toggle. Defaults true
     * so existing behavior (annual leave excluding weekends) is preserved
     * unless a type explicitly opts out.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_types', 'exclude_non_working_days')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('exclude_non_working_days')->default(true)->after('exclude_public_holidays');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leave_types', 'exclude_non_working_days')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('exclude_non_working_days');
        });
    }
};
