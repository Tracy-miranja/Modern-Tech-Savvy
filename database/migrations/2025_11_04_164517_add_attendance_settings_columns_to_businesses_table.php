<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // flags
            $table->boolean('enforce_geofence')->default(false)->after('updated_at');
            $table->boolean('enforce_mac')->default(false)->after('enforce_geofence');

            // extra geofences as JSON: [{"lat":-1.286389,"lng":36.817223,"radius_m":150}, ...]
            // Requires MySQL 5.7+/MariaDB 10.2.7+ for native JSON.
            $table->json('extra_geofences')->nullable()->after('enforce_mac');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['enforce_geofence', 'enforce_mac', 'extra_geofences']);
            
        });
    }
};
