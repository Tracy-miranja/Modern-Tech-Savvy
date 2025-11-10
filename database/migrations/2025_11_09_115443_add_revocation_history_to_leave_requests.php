<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // JSON works on MySQL 5.7+/MariaDB 10.2.7+/Postgres/SQLite (stored as text on SQLite)
            // If your MySQL is older, swap to ->longText() instead.
            $table->json('revocation_history')->nullable()->after('total_days');
        });

        // Initialize existing rows to [] so casting to array won’t break
        try {
            DB::table('leave_requests')->whereNull('revocation_history')->update([
                'revocation_history' => DB::raw("JSON_ARRAY()")
            ]);
        } catch (\Throwable $e) {
            // Fallback for engines without JSON_ARRAY() (e.g. SQLite)
            DB::table('leave_requests')->whereNull('revocation_history')->update([
                'revocation_history' => '[]'
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('revocation_history');
        });
    }
};
