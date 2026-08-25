<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('employee_payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payroll_period');
            $table->decimal('basic_salary', 12, 2)->nullable();
            $table->json('allowances')->nullable();
            $table->json('deductions')->nullable();
            $table->json('reliefs')->nullable();
            $table->json('overtime')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_settings');
    }
};
