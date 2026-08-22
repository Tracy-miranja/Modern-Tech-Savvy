<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persists the "Getting Started" dashboard panel's dismissed state per
     * business - not session-based, since a session-only dismiss would
     * reappear every new login/device even after the business finished (or
     * chose to ignore) setup.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->timestamp('setup_guide_dismissed_at')->nullable()->after('non_working_days');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('setup_guide_dismissed_at');
        });
    }
};
