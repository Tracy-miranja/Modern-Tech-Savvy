<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `LeavePeriod` model/controller have always read/written `is_active`,
     * but no migration ever created the column - every leave period
     * create/update through the UI throws "Unknown column 'is_active'".
     */
    public function up(): void
    {
        if (Schema::hasColumn('leave_periods', 'is_active')) {
            return;
        }

        Schema::table('leave_periods', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('end_date');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_periods', 'is_active')) {
            Schema::table('leave_periods', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
