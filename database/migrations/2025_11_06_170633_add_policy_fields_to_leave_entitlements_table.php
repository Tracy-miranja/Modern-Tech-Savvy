<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('leave_entitlements', 'policy_snapshot')) {
            return;
        }

        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->longText('policy_snapshot')->nullable()->after('accrued_days');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_entitlements', 'policy_snapshot')) {
            Schema::table('leave_entitlements', function (Blueprint $table) {
                $table->dropColumn('policy_snapshot');
            });
        }
    }
};
