<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Free-text handover summary (e.g. "what I was working on") shown to the
     * approver and the reliever, alongside the existing single-file attachment.
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_requests', 'handover_notes')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->text('handover_notes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'handover_notes')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('handover_notes');
            });
        }
    }
};
