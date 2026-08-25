<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (!Schema::hasColumn('leave_entitlements', 'carryover_days')) {
            Schema::table('leave_entitlements', function (Blueprint $table) {
                $table->decimal('carryover_days', 8, 2)->default(0.00)->after('accrued_days');
            });
        }

        if (Schema::hasColumn('leave_entitlements', 'carry_forward')) {
            Schema::table('leave_entitlements', function (Blueprint $table) {
                $table->dropColumn('carry_forward');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_entitlements', 'carryover_days')) {
            Schema::table('leave_entitlements', function (Blueprint $table) {
                $table->dropColumn('carryover_days');
            });
        }

        if (!Schema::hasColumn('leave_entitlements', 'carry_forward')) {
            Schema::table('leave_entitlements', function (Blueprint $table) {
                $table->decimal('carry_forward', 5, 2)->nullable();
            });
        }
    }
};
