<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
