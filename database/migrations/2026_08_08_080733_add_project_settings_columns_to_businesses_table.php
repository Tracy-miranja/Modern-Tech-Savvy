<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project Management settings - plain scalar column on businesses, same
 * precedent as Learning Management's learning_* columns (no generic
 * per-business settings table exists in this app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('project_task_due_reminder_days')->default(2);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('project_task_due_reminder_days');
        });
    }
};
