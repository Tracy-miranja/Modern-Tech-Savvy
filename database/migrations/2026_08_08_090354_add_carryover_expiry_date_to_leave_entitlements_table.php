<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Computed once when the entitlement is created (new period start +
 * policy's carryover_expiry_months), null if the policy has no expiry
 * configured. leave:run-accruals forfeits unused carryover once this date
 * passes - see LeavePolicyService::calculateCarryover()'s docblock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->date('carryover_expiry_date')->nullable()->after('carryover_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->dropColumn('carryover_expiry_date');
        });
    }
};
