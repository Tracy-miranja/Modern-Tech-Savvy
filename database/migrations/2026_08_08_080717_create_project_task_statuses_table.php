<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business-configurable Kanban columns for the Project Management board -
 * same shape as Disciplinary's stage types (business-scoped, sequence_order,
 * lazily seeded with defaults on first use). is_done marks a status as
 * "task complete" (stamps ProjectTask.completed_at when a task moves here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sequence_order');
            $table->string('color')->default('#6c757d');
            $table->boolean('is_done')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_statuses');
    }
};
