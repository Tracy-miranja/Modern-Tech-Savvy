<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
