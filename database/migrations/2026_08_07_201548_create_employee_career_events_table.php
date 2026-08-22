<?php

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee career history: promotions and salary increments, one row per
 * event with structured old/new values (never free-text) so it's actually
 * reportable later (average increment %, promotions by department, etc).
 *
 * event_type is a plain string, NOT a DB enum - the existing
 * employee_contract_actions.action_type IS a DB enum, and adding
 * 'suspension' to it required a raw ALTER TABLE migration. A string field
 * validated at the app level means a future event type (e.g. 'transfer',
 * 'title_change') never needs a migration.
 *
 * Respects a future effective_date (user's explicit choice over "apply
 * immediately"): status starts 'pending' and only flips to 'applied' -
 * updating EmploymentDetail/EmployeePaymentDetail - once effective_date is
 * reached, via the daily `career-events:apply-pending` scheduled command
 * (see EmployeeCareerEventService::applyDuePendingEvents()). Recording an
 * event with today/a past effective_date applies it immediately instead of
 * waiting for tomorrow's run.
 */
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
            $table->string('status')->default('pending'); // pending, applied, reversed
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
