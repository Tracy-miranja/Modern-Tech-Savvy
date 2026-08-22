<?php

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project Management module - the last of the originally-unimplemented
 * modules (see Asset/Learning Management for the precedent this follows).
 * status is a plain string, not a DB enum, per the case_type-enum lesson.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignIdFor(Department::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Employee::class, 'manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('planning'); // planning, active, on_hold, completed, cancelled
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
