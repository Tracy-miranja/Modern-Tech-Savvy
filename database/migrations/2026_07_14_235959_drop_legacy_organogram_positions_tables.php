<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Retires the January organogram_positions/organogram_position_holders
 * tables (a position/job-slot hierarchy tree with holders) ahead of the
 * hrmbackend-derived organogram system, which reuses the
 * organogram_positions table name for a different shape (employee<->role
 * assignment via OrganogramRole - see 2026_07_15_000005). Nothing in the
 * app reads these tables once OrganogramPosition/OrganogramPositionHolder
 * are removed in the same sync, so this is safe to run before that
 * migration creates the new organogram_positions table under the same
 * name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('organogram_position_holders');
        Schema::dropIfExists('organogram_positions');
    }

    public function down(): void
    {
        // Irreversible: the old position/holder shape is gone once the
        // new organogram_positions table (different columns) takes the name.
    }
};
