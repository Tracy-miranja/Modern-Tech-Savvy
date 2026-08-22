<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->decimal('days_pending', 8, 2)->default(0)->after('days_taken');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {
            $table->dropColumn('days_pending');
        });
    }
};
