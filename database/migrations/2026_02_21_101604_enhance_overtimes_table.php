<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('overtimes', function (Blueprint $table) {

            $table->enum('overtime_type', ['regular', 'holiday', 'manual'])->default('regular')->after('overtime_hours');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('set null')->after('employee_id');
        });
    }

    public function down()
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropColumn([
                'overtime_type',
                'status',
                'approved_at',
                'rejection_reason',
                'attendance_id'
            ]);
        });
    }
};