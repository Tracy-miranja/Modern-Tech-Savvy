<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `LeavePolicyService::resolvePolicy()` and `LeavePolicy::scopeActive()`
     * have always filtered on `is_active`, but no migration ever created the
     * column - every policy resolution (and therefore every entitlement
     * assignment in LeaveEntitlementController::store()) has been throwing
     * "Unknown column 'is_active'" against this database.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_policies', 'is_active')) {
            return;
        }

        Schema::table('leave_policies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('end_date');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_policies', 'is_active')) {
            Schema::table('leave_policies', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
