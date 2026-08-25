<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

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
