<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Phase 01 of the OKR strategy: turns the flat objective list into a
     * 3-tier cascade (company pillar -> departmental OKR -> individual KR)
     * with a bottom-up proposal path.
     */
    public function up(): void
    {
        if (Schema::hasColumn('performance_objectives', 'scope')) {
            return;
        }

        Schema::table('performance_objectives', function (Blueprint $table) {
            $table->enum('scope', ['company', 'department', 'individual'])
                ->default('individual')
                ->after('employee_id');
            $table->unsignedBigInteger('parent_objective_id')->nullable()->after('scope');
            $table->unsignedBigInteger('department_id')->nullable()->after('parent_objective_id');
            // Existing rows predate the proposal workflow - treat them as
            // already-approved so nothing already on the board disappears.
            $table->enum('alignment_status', ['draft', 'proposed', 'approved'])
                ->default('approved')
                ->after('status');

            $table->foreign('parent_objective_id')->references('id')->on('performance_objectives')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        // weight has always been a plain default(100) column; every objective
        // now owning an explicit weight is enforced at the application layer
        // (required validation, no silent fallback) rather than here.
    }

    public function down(): void
    {
        if (!Schema::hasColumn('performance_objectives', 'scope')) {
            return;
        }

        Schema::table('performance_objectives', function (Blueprint $table) {
            $table->dropForeign(['parent_objective_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['scope', 'parent_objective_id', 'department_id', 'alignment_status']);
        });
    }
};
