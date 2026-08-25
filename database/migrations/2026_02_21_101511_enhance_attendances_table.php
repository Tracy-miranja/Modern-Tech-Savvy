<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {

            $table->boolean('is_working_day')->default(true)->after('is_absent');
            $table->boolean('is_holiday')->default(false)->after('is_working_day');

            $table->decimal('regular_hours', 5, 2)->default(0)->after('overtime_hours');
            $table->decimal('late_minutes', 5, 2)->default(0)->after('regular_hours');
            $table->decimal('early_departure_minutes', 5, 2)->default(0)->after('late_minutes');

            $table->decimal('overtime_regular', 5, 2)->default(0)->after('overtime_hours');
            $table->decimal('overtime_holiday', 5, 2)->default(0)->after('overtime_regular');

            $table->time('expected_clock_in')->nullable()->after('clock_in');
            $table->time('expected_clock_out')->nullable()->after('clock_out');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'is_working_day',
                'is_holiday',
                'regular_hours',
                'late_minutes',
                'early_departure_minutes',
                'overtime_regular',
                'overtime_holiday',
                'expected_clock_in',
                'expected_clock_out'
            ]);
        });
    }
};