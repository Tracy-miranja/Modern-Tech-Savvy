<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up()
{
    Schema::table('payroll_formulas', function (Blueprint $table) {
        $table->string('country')->nullable()->after('business_id');
    });
}

public function down()
{
    Schema::table('payroll_formulas', function (Blueprint $table) {
        $table->dropColumn('country');
    });
}
};
