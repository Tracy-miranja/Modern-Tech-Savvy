<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {

            $table->json('revocation_history')->nullable()->after('total_days');
        });

        try {
            DB::table('leave_requests')->whereNull('revocation_history')->update([
                'revocation_history' => DB::raw("JSON_ARRAY()")
            ]);
        } catch (\Throwable $e) {

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
