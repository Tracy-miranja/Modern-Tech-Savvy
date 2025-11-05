<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('registered_device_mac', 64)->nullable()->after('updated_at');
            // You may add ->unique() if you want one-employee-per-device constraint:
            // $table->unique('registered_device_mac');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // dropUnique if you added it above
            // $table->dropUnique(['registered_device_mac']);
            $table->dropColumn(['registered_device_mac']);
        });
    }
};
