<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('work_schedule_id')->nullable()->after('business_id');
            $table->unsignedBigInteger('shift_id')->nullable()->after('work_schedule_id');

            $table->index(['business_id', 'date']);
            $table->index(['employee_id', 'date']);

            // FK constraints:
            // $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->nullOnDelete();
            // $table->foreign('shift_id')->references('id')->on('shifts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            //
        });
    }
};
