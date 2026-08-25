<?php

use App\Models\Business;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTaskCategory;
use App\Models\ProjectTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ProjectTaskStatus::class)->constrained('project_task_statuses')->restrictOnDelete();
            $table->foreignIdFor(ProjectTaskCategory::class)->nullable()->constrained('project_task_categories')->nullOnDelete();
            $table->foreignIdFor(Employee::class, 'assignee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_reminder_sent_at')->nullable();
            $table->timestamp('overdue_reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
