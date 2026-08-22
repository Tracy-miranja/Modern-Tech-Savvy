<?php

use App\Models\Business;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Time Tracking - a manual daily hours log against a project, optionally
 * against a specific task. Deliberately a simple log entry (date + hours),
 * not a start/stop timer - keeps scope proportionate to an HR-suite add-on
 * rather than a dedicated timesheet product.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ProjectTask::class)->nullable()->constrained('project_tasks')->nullOnDelete();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('hours', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_time_logs');
    }
};
