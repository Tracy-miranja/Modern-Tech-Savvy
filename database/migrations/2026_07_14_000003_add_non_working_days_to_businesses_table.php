<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The business-wide weekly rest days (e.g. Saturday/Sunday) - shown on
     * the leave calendar and excluded from every leave-day calculation,
     * distinct from the per-leave-type excluded_days toggle. Stored as a
     * JSON array of 0-6 weekday numbers (0 = Sunday), matching the
     * convention already used by WorkSchedule.working_days.
     */
    public function up(): void
    {
        if (Schema::hasColumn('businesses', 'non_working_days')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->json('non_working_days')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('businesses', 'non_working_days')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('non_working_days');
        });
    }
};
