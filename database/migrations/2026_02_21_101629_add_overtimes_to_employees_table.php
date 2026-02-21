<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            // Overtime eligibility
            $table->boolean('is_overtime_eligible')->default(true)->after('registered_device_mac');
            $table->decimal('overtime_rate_regular', 5, 2)->nullable()->after('is_overtime_eligible'); // multiplier (e.g., 1.5)
            $table->decimal('overtime_rate_holiday', 5, 2)->nullable()->after('overtime_rate_regular'); // multiplier (e.g., 2.0)
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'is_overtime_eligible',
                'overtime_rate_regular',
                'overtime_rate_holiday'
            ]);
        });
    }
};