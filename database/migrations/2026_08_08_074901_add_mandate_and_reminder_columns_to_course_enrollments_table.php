<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->foreignId('course_mandate_id')->nullable()->after('course_session_id')
                ->constrained('course_mandates')->nullOnDelete();
            $table->timestamp('session_reminder_sent_at')->nullable();
            $table->timestamp('certificate_expiry_reminder_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_mandate_id');
            $table->dropColumn(['session_reminder_sent_at', 'certificate_expiry_reminder_sent_at']);
        });
    }
};
