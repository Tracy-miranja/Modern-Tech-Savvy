<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-healing repair for a migration-ordering hazard:
 * 2026_07_15_000005_create_organogram_positions_table.php only creates
 * organogram_positions when the table doesn't already exist - correct
 * when 2026_07_14_235959_drop_legacy_organogram_positions_tables.php
 * (which drops the January-era organogram_positions/
 * organogram_position_holders tables, a completely different shape: name/
 * title/code/parent_id/personnel_position_id/level/sort_order, no
 * organogram_role_id or employee_id at all) has already run first. If a
 * database's migration history ever has these two out of order or the
 * drop never actually ran, 2026_07_15_000005 silently no-ops against the
 * old table, gets marked as run anyway, and every OrganogramPosition
 * query then fails with "column organogram_role_id doesn't exist" -
 * exactly the error this exists to fix, permanently, regardless of how
 * that database got into this state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organogram_positions')) {
            return;
        }

        if (Schema::hasColumn('organogram_positions', 'organogram_role_id')) {
            return;
        }

        // Old-shape table detected - it predates the current organogram
        // system entirely and nothing in the app reads it (see
        // 2026_07_14_235959's docblock), so it's safe to replace outright.
        Schema::dropIfExists('organogram_position_team');
        Schema::dropIfExists('organogram_position_department');
        Schema::dropIfExists('organogram_position_holders');
        Schema::dropIfExists('organogram_positions');

        Schema::create('organogram_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organogram_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_role_id', 'employee_id'], 'organogram_positions_role_employee_unique');
        });

        Schema::create('organogram_position_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organogram_position_id')->constrained('organogram_positions')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_position_id', 'department_id'], 'organogram_position_department_unique');
        });

        Schema::create('organogram_position_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organogram_position_id')->constrained('organogram_positions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_position_id', 'team_id'], 'organogram_position_team_unique');
        });
    }

    public function down(): void
    {
        // Irreversible - the old position shape it replaced is gone.
    }
};
