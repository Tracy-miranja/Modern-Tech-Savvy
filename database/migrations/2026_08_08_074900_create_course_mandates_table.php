<?php

use App\Models\Business;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a course mandatory/compliance-required for a scope of employees -
 * mirrors mandatory_leave_periods' scope_type/scope_ids shape. Deliberately
 * additive-only (see CourseMandate::autoEnroll()): narrowing or deleting a
 * mandate never auto-unenrolls anyone, unlike mandatory leave's deduction
 * reversal - unenrolling here would destroy real progress/certificates,
 * where a leave-day deduction is safely reversible arithmetic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Course::class)->constrained('courses')->cascadeOnDelete();
            $table->string('scope_type'); // organization, department, job_category
            $table->json('scope_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_mandates');
    }
};
