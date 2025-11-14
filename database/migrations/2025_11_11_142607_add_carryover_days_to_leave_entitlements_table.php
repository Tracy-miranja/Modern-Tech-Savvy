<?php

// database/migrations/2025_11_11_000001_add_carryover_days_to_leave_entitlements.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->decimal('carryover_days', 8, 2)->default(0)->after('accrued_days');
        });
    }
    public function down(): void {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->dropColumn('carryover_days');
        });
    }
};

