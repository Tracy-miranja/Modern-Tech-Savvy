<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('leave_delegations', 'declined_at')) {
            return;
        }

        Schema::table('leave_delegations', function (Blueprint $table) {
            $table->timestamp('declined_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_delegations', 'declined_at')) {
            Schema::table('leave_delegations', function (Blueprint $table) {
                $table->dropColumn('declined_at');
            });
        }
    }
};
