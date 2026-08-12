<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leave_types', 'exclude_public_holidays')) {
            return;
        }

        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('exclude_public_holidays')->default(true)->after('excluded_dates');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_types', 'exclude_public_holidays')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropColumn('exclude_public_holidays');
            });
        }
    }
};
