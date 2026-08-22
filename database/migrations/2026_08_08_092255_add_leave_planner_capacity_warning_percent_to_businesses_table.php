<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave Planner capacity warning threshold - the Business Leave Calendar's
 * "Planner" timeline view flags a day where more than this percentage of a
 * filtered department/team is on leave at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedTinyInteger('leave_planner_capacity_warning_percent')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('leave_planner_capacity_warning_percent');
        });
    }
};
