<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stage_type_id is additive, not a replacement for the existing case_type
 * enum - plenty of code (mailables, the enum-driven form) still reads
 * case_type, so it stays as-is. stage_type_id starts null on every
 * existing row; DisciplinaryStageTypeService::ensureSeeded() backfills it
 * (matching case_type -> the newly-seeded stage's slug) the first time a
 * business's stages actually get seeded, since stage types are lazily
 * created per business rather than in this migration.
 *
 * response_due_at/employee_response/employee_responded_at generalize
 * "Show Cause" - any stage configured with requires_response=true can
 * demand and capture an employee response, not just one hardcoded case type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->foreignId('stage_type_id')->nullable()->after('case_type')
                ->constrained('disciplinary_stage_types')->nullOnDelete();
            $table->timestamp('response_due_at')->nullable()->after('resolution_notes');
            $table->text('employee_response')->nullable()->after('response_due_at');
            $table->timestamp('employee_responded_at')->nullable()->after('employee_response');
        });
    }

    public function down(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->dropForeign(['stage_type_id']);
            $table->dropColumn(['stage_type_id', 'response_due_at', 'employee_response', 'employee_responded_at']);
        });
    }
};
