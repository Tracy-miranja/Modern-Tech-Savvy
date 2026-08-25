<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedTinyInteger('payroll_remittance_deadline_day')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('payroll_remittance_deadline_day');
        });
    }
};
