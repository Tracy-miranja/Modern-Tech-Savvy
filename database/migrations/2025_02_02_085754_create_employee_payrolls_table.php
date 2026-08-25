<?php

use App\Models\Payroll;
use App\Models\Employee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payroll::class);
            $table->foreignIdFor(Employee::class);
            $table->integer('basic_salary')->nullable();
            $table->integer('housing_allowance')->nullable();
            $table->integer('gross_pay')->nullable();
            $table->integer('paye')->nullable();
            $table->integer('nhif')->nullable();
            $table->integer('nssf')->nullable();
            $table->integer('pension')->nullable();
            $table->integer('housing_levy')->nullable();
            $table->integer('taxable_income')->nullable();
            $table->integer('personal_relief')->nullable();
            $table->integer('pay_after_tax')->nullable();
            $table->integer('deductions_after_tax')->nullable();
            $table->integer('net_pay')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payrolls');
    }
};
