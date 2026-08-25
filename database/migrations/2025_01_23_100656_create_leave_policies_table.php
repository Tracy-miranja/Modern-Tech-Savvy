<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_type_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('job_category_id')->nullable()->constrained();
            $table->enum('gender_applicable', ['all', 'male', 'female'])->default('all');
            $table->integer('default_days');
            $table->enum('accrual_frequency', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('accrual_amount', 5, 2);
            $table->integer('max_carryover_days')->default(0);
            $table->boolean('prorated_for_new_employees')->default(true);
            $table->integer('minimum_service_days_required')->default(0);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
