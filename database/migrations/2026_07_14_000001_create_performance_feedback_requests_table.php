<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasTable('performance_feedback_requests')) {
            return;
        }

        Schema::create('performance_feedback_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('reviewer_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'submitted', 'declined'])->default('pending');
            $table->timestamps();

            $table->unique(
                ['performance_cycle_id', 'subject_employee_id', 'reviewer_employee_id'],
                'perf_feedback_unique_nomination'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_feedback_requests');
    }
};
