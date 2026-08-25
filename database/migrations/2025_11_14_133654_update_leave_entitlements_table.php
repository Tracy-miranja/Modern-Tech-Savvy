<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {

            if (!Schema::hasColumn('leave_entitlements', 'carryover_days')) {
                $table->decimal('carryover_days', 8, 2)
                    ->default(0)
                    ->after('accrued_days');
            }

            if (!Schema::hasColumn('leave_entitlements', 'policy_snapshot')) {
                $table->json('policy_snapshot')
                    ->nullable()
                    ->after('carryover_days');
            }

            if (!Schema::hasColumn('leave_entitlements', 'last_accrued_at')) {
                $table->timestamp('last_accrued_at')
                    ->nullable()
                    ->after('policy_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {

            if (Schema::hasColumn('leave_entitlements', 'carryover_days')) {
                $table->dropColumn('carryover_days');
            }
            if (Schema::hasColumn('leave_entitlements', 'policy_snapshot')) {
                $table->dropColumn('policy_snapshot');
            }
            if (Schema::hasColumn('leave_entitlements', 'last_accrued_at')) {
                $table->dropColumn('last_accrued_at');
            }
        });
    }
};
