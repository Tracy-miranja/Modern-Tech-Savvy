<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recreates a migration that ran against existing databases (recorded in
     * the `migrations` table as batch 37) but whose file was never committed
     * to the repo. Guarded so it stays a no-op where already applied.
     *
     * Replaces the original `carry_forward` column (created with no default,
     * causing insert failures under strict SQL mode and never wired into the
     * model/controllers) with `carryover_days` (NOT NULL, default 0.00).
     */
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
