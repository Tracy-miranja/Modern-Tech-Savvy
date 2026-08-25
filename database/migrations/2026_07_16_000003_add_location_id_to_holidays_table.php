<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('holidays', 'location_id')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('business_id')
                ->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('holidays', 'location_id')) {
            return;
        }

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
