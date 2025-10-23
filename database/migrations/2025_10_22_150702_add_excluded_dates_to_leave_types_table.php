<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_excluded_dates_to_leave_types_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->json('excluded_dates')->nullable()->after('excluded_days'); // ['2025-12-25','2026-01-01', ...]
        });
    }

    public function down(): void {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('excluded_dates');
        });
    }
};
