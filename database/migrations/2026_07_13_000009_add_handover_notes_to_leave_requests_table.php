<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

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
