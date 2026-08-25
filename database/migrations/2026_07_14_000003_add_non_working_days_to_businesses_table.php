<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('businesses', 'non_working_days')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->json('non_working_days')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('businesses', 'non_working_days')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('non_working_days');
        });
    }
};
