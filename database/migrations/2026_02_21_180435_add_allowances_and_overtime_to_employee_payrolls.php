<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->longText('allowances')->nullable()->after('deductions');
        $table->longText('overtime')->nullable()->after('allowances');
    });
}

public function down()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->dropColumn(['allowances', 'overtime']);
    });
}
};
