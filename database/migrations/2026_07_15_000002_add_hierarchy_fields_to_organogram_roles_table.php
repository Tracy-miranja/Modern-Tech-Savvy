<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Replaces the old strict numeric `level` ladder (which couldn't
     * express peers or non-linear structures, e.g. an ED who outranks or
     * is a peer of the MD) with an explicit "this role reports to that
     * role" edge. `level` is left in place (still useful as a rough
     * display-order hint) but is no longer authoritative for manager
     * resolution - see Employee::computeTemplateManagerId().
     *
     * spatie_role_name maps this organogram position to a real Spatie
     * permission role, so holding the position grants the matching
     * system permissions instead of the two systems being unrelated.
     */
    public function up(): void
    {
        if (Schema::hasColumn('organogram_roles', 'reports_to_role_id')) {
            return;
        }

        Schema::table('organogram_roles', function (Blueprint $table) {
            $table->foreignId('reports_to_role_id')->nullable()->after('level')
                ->constrained('organogram_roles')->nullOnDelete();
            $table->string('spatie_role_name')->nullable()->after('reports_to_role_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('organogram_roles', 'reports_to_role_id')) {
            return;
        }

        Schema::table('organogram_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reports_to_role_id');
            $table->dropColumn('spatie_role_name');
        });
    }
};
