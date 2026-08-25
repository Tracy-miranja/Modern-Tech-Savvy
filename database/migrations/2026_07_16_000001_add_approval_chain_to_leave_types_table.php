<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

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
