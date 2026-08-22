<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Learning Management settings - plain scalar columns directly on
 * businesses, matching the enforce_geofence/non_working_days precedent
 * (this app has no generic per-business settings table). Prefixed
 * `learning_` since these are the first module-specific settings added
 * this way rather than a core business concern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedInteger('learning_certificate_validity_months')->nullable();
            $table->string('learning_certificate_number_prefix')->nullable();
            $table->unsignedInteger('learning_session_reminder_days')->default(3);
            $table->unsignedInteger('learning_certificate_expiry_reminder_days')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'learning_certificate_validity_months',
                'learning_certificate_number_prefix',
                'learning_session_reminder_days',
                'learning_certificate_expiry_reminder_days',
            ]);
        });
    }
};
