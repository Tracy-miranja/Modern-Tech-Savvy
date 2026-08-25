<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
            $table->year('payrun_year')->after('staff');
            $table->unsignedTinyInteger('payrun_month')->after('payrun_year');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->date('start_date')->after('staff');
            $table->date('end_date')->after('start_date');
            $table->dropColumn(['payrun_year', 'payrun_month']);
        });
    }
};
