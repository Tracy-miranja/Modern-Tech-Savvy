<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        if (Schema::hasColumn('employees', 'location_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete()->after('business_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'location_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
