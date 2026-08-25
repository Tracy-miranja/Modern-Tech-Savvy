<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('employment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained();
            $table->foreignId('job_category_id')->constrained();
            $table->foreignId('shift_id')->nullable()->constrained();
            $table->date('employment_date');
            $table->date('probation_end_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('retirement_date')->nullable();
            $table->enum('employment_term', ['contract', 'fulltime', 'permanent']);
            $table->text('job_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_details');
    }
};
