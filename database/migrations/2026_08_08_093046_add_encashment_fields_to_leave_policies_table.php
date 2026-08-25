php art<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->boolean('is_encashable')->default(false)->after('min_interval_days');
            $table->unsignedInteger('max_encashable_days')->nullable()->after('is_encashable');
        });
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn(['is_encashable', 'max_encashable_days']);
        });
    }
};
