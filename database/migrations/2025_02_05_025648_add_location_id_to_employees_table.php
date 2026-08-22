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
        // create_employees_table already adds location_id as a required FK
        // on a fresh install - this migration only still applies on an
        // older database that predates that column being added there.
        if (Schema::hasColumn('employees', 'location_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'location_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
