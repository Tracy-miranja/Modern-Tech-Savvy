<?php

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_career_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->date('effective_date');

            $table->foreignIdFor(JobCategory::class, 'old_job_category_id')->nullable()->constrained('job_categories')->nullOnDelete();
            $table->foreignIdFor(JobCategory::class, 'new_job_category_id')->nullable()->constrained('job_categories')->nullOnDelete();
            $table->foreignIdFor(Department::class, 'old_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignIdFor(Department::class, 'new_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->decimal('old_salary', 12, 2)->nullable();
            $table->decimal('new_salary', 12, 2)->nullable();

            $table->string('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->foreignIdFor(User::class, 'issued_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_career_events');
    }
};
