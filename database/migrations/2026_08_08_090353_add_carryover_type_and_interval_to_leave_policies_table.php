<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * carryover_type/carryover_value replace "carryover = min(unused,
 * max_carryover_days)" as the only option - 'full' reproduces that exact
 * legacy behavior (default, non-breaking), 'fixed' carries a flat day
 * count, 'percent' carries a percentage of the unused balance.
 * max_carryover_days stays as the hard ceiling on top of whichever type is
 * chosen. carryover_expiry_months is how many months into the new period
 * carried-over days remain usable before being forfeited (null = never).
 * min_interval_days enforces a cooldown between two consecutive requests
 * of the same leave type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->string('carryover_type')->default('full')->after('max_carryover_days');
            $table->decimal('carryover_value', 6, 2)->nullable()->after('carryover_type');
            $table->unsignedInteger('carryover_expiry_months')->nullable()->after('carryover_value');
            $table->unsignedInteger('min_interval_days')->nullable()->after('minimum_service_days_required');
        });
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn(['carryover_type', 'carryover_value', 'carryover_expiry_months', 'min_interval_days']);
        });
    }
};
