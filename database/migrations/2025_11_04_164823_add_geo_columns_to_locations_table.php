<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // latitude/longitude up to 7 decimals
            $table->decimal('latitude', 10, 7)->nullable()->after('physical_address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // radius in meters (nullable: fall back to 150 in app logic)
            $table->integer('radius_m')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius_m']);
        });
    }
};

