<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('device_mac', 64)->nullable()->after('updated_at');

            $table->decimal('punch_latitude', 10, 7)->nullable()->after('device_mac');
            $table->decimal('punch_longitude', 10, 7)->nullable()->after('punch_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['device_mac', 'punch_latitude', 'punch_longitude']);
        });
    }
};
