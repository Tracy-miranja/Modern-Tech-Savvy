<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original schema only models acceptance (delegate_accepted +
     * accepted_at), with no way for a reliever to decline - needed so the
     * requester/manager knows to pick someone else instead of the request
     * silently sitting unacknowledged forever.
     */
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
