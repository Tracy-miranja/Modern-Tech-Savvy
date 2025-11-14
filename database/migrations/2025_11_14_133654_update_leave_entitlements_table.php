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
            // Add new fields
            $table->decimal('carryover_days', 8, 2)
                ->default(0)
                ->after('accrued_days');

            $table->json('policy_snapshot')
                ->nullable()
                ->after('carryover_days');

            // Create last_accrued_at column since it does NOT exist
            $table->timestamp('last_accrued_at')
                ->nullable()
                ->after('policy_snapshot');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_entitlements', function (Blueprint $table) {


           // Drop columns
            $table->dropColumn([
                'carryover_days',
                'policy_snapshot',
                'last_accrued_at'
            ]);
        });
    }
};
