<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A proper incremental adjustment (+/- days with a required reason),
     * separate from the policy-driven entitled_days, so HR corrections
     * (data-entry fixes, goodwill grants) are visible and auditable
     * instead of silently folded into the policy number or requiring a
     * blunt overwrite of every field. Cumulative across multiple
     * adjustments; the reason shown is always the most recent one - the
     * full history lives in the existing activity log (LogsActivity).
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_entitlements', 'adjustment_days')) {
            return;
        }

        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->decimal('adjustment_days', 8, 2)->default(0)->after('carryover_days');
            $table->text('adjustment_reason')->nullable()->after('adjustment_days');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leave_entitlements', 'adjustment_days')) {
            return;
        }

        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->dropColumn(['adjustment_days', 'adjustment_reason']);
        });
    }
};
