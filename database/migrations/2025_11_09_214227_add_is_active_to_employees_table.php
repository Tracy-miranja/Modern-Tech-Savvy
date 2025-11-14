<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_is_active_to_employees_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('business_id');
            // If you soft-delete, you likely already have deleted_at
        });
    }

    public function down(): void {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
