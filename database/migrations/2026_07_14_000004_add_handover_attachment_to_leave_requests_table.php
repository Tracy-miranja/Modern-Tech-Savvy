<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

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
