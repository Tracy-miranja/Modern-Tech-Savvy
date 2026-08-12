<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * An optional file to go with handover_notes - a separate column
     * (rather than reusing the existing `attachment` field) since that one
     * is already semantically tied to LeaveType.requires_attachment's
     * conditionally-required documentation flow.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_requests', 'handover_attachment')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('handover_attachment')->nullable()->after('handover_notes');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('leave_requests', 'handover_attachment')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn('handover_attachment');
        });
    }
};
