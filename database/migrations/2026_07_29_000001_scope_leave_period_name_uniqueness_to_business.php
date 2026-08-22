<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The original migration put a table-wide unique constraint on `name`,
     * so a leave period name was blocked once ANY business used it. It
     * should only be unique within a single business.
     */
    public function up(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->dropUnique('leave_periods_name_unique');
            $table->unique(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'name']);
            $table->unique('name');
        });
    }
};
