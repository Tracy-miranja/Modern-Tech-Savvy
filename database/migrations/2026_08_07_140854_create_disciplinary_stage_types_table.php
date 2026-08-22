<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fully business-configurable disciplinary stages, replacing the hardcoded
 * Warning::STAGES ladder (see GUIDE plan Phase 3 - user explicitly asked
 * for fully custom stages per business, not just toggle+approvers).
 * Lazily seeded with the 5 stock stages (Verbal -> Written -> Final ->
 * Suspension -> Termination) the first time a business's Configure screen
 * is opened - see DisciplinaryStageTypeService::ensureSeeded().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_stage_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sequence_order');
            $table->boolean('is_terminal')->default(false);
            $table->boolean('requires_response')->default(false);
            $table->string('approver_role')->nullable();
            $table->json('notify_roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_stage_types');
    }
};
