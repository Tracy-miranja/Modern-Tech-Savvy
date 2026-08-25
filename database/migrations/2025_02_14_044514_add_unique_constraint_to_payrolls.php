<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unique(['payrun_year', 'payrun_month', 'business_id', 'location_id'], 'payroll_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropUnique('payroll_unique');
        });
    }
};
