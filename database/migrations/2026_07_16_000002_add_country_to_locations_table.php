<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('locations', 'country')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->string('country')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('locations', 'country')) {
            return;
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
