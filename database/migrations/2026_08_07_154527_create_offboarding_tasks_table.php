<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sensible default tasks (asset return, access revocation, exit interview,
 * final settlement/clearance, knowledge handover) seeded per checklist by
 * OffboardingChecklistService, but freely editable per case - a plain row
 * per task, not a business-level "Configure" catalog like Disciplinary's
 * stage types, per the GUIDE plan wording.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('offboarding_checklists')->cascadeOnDelete();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('task_key')->nullable(); // e.g. 'asset_return' for the stock tasks, null for custom ones
            $table->string('name');
            $table->unsignedInteger('sequence_order')->default(1);
            $table->boolean('is_done')->default(false);
            $table->foreignIdFor(User::class, 'completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_tasks');
    }
};
