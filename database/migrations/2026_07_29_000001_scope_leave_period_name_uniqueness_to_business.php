<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

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
