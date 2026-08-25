<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('leave_policies', 'is_active')) {
            return;
        }

        Schema::table('leave_policies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('end_date');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_policies', 'is_active')) {
            Schema::table('leave_policies', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
