<?php

use App\Models\Business;
use App\Models\Employee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained();
            $table->unsignedBigInteger('reviewer_id')->nullable(); // employees.id of the line manager
            $table->decimal('kpi_score', 5, 2)->nullable();
            $table->decimal('okr_score', 5, 2)->nullable();
            $table->decimal('competency_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('self_assessment')->nullable();
            $table->text('manager_assessment')->nullable();
            $table->enum('status', ['pending_self', 'pending_manager', 'completed'])->default('pending_self');
            $table->timestamp('self_submitted_at')->nullable();
            $table->timestamp('manager_submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['performance_cycle_id', 'employee_id']);
            $table->foreign('reviewer_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
