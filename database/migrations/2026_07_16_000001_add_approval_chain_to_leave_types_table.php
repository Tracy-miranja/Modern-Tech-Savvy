<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Explicitly names WHO approves each level of a leave type's approval
     * chain (a JSON array of 'organogram'|'hr'|'department_head', one
     * entry per level) - `approval_levels` was previously just a bare
     * count with a comment claiming "1=HR only, 2=Manager+HR" but no
     * field actually encoding that mapping. Businesses that want every
     * leave request routed straight to HR (skipping the organogram
     * entirely) can now configure that explicitly instead of it being
     * implied by nothing.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_types', 'approval_chain')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->json('approval_chain')->nullable()->after('approval_levels');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leave_types', 'approval_chain')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('approval_chain');
        });
    }
};
