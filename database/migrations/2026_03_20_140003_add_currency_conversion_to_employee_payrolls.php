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
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->string('employee_currency', 10)->default('KES')->after('net_pay');
        $table->string('tax_currency', 10)->default('KES')->after('employee_currency');
        $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('tax_currency');
        $table->decimal('basic_salary_orig', 15, 2)->nullable()->after('exchange_rate');
        $table->decimal('gross_pay_orig', 15, 2)->nullable()->after('basic_salary_orig');
        $table->decimal('net_pay_orig', 15, 2)->nullable()->after('gross_pay_orig');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            //
        });
    }
};
