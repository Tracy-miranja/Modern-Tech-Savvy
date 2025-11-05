<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('physical_address');
            }

            if (!Schema::hasColumn('locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (!Schema::hasColumn('locations', 'radius_m')) {
                $table->integer('radius_m')->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('locations', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('locations', 'radius_m')) {
                $table->dropColumn('radius_m');
            }
        });
    }
};
