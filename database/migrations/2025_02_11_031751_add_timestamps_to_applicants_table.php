<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // create_applicants_table already calls $table->timestamps() on a
        // fresh install - this migration only still applies on an older
        // database that predates those columns being added there.
        if (Schema::hasColumn('applicants', 'created_at')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('applicants', 'created_at')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
