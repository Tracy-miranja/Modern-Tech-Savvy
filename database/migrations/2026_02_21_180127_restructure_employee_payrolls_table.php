<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  public function up()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {

        $table->unsignedBigInteger('employee_payment_detail_id')->nullable()->after('employee_id');
        $table->decimal('paye_before_reliefs', 15, 2)->nullable()->after('paye');
        $table->decimal('shif', 15, 2)->nullable()->after('paye_before_reliefs');
        $table->decimal('helb', 15, 2)->nullable()->after('housing_levy');
        $table->decimal('insurance_relief', 15, 2)->nullable()->after('personal_relief');
        $table->decimal('loan_repayment', 15, 2)->nullable()->after('pay_after_tax');
        $table->decimal('advance_recovery', 15, 2)->nullable()->after('loan_repayment');
        $table->string('bank_name')->nullable()->after('deductions');
        $table->string('account_number')->nullable()->after('bank_name');
        $table->integer('attendance_present')->nullable()->after('account_number');
        $table->integer('attendance_absent')->nullable()->after('attendance_present');
        $table->integer('days_in_month')->nullable()->after('attendance_absent');

        $table->decimal('basic_salary', 15, 2)->nullable()->change();
        $table->decimal('housing_allowance', 15, 2)->nullable()->change();
        $table->decimal('gross_pay', 15, 2)->nullable()->change();
        $table->decimal('paye', 15, 2)->nullable()->change();
        $table->decimal('nssf', 15, 2)->nullable()->change();
        $table->decimal('pension', 15, 2)->nullable()->change();
        $table->decimal('housing_levy', 15, 2)->nullable()->change();
        $table->decimal('taxable_income', 15, 2)->nullable()->change();
        $table->decimal('personal_relief', 15, 2)->nullable()->change();
        $table->decimal('pay_after_tax', 15, 2)->nullable()->change();
        $table->decimal('deductions_after_tax', 15, 2)->nullable()->change();
        $table->decimal('net_pay', 15, 2)->nullable()->change();

        $table->index('employee_payment_detail_id');
    });
}

public function down()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->dropColumn([
            'employee_payment_detail_id', 'paye_before_reliefs', 'shif',
            'helb', 'insurance_relief', 'loan_repayment', 'advance_recovery',
            'bank_name', 'account_number', 'attendance_present',
            'attendance_absent', 'days_in_month',
        ]);
    });
}
};
