<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A holiday can be business-wide (location_id null - applies to every
     * employee regardless of which branch they're at, e.g. a company
     * founder's day) or scoped to one specific location/branch (e.g. a
     * public holiday that only applies to the country that location is
     * in). Holiday::getHolidaysInRange() combines both when given a
     * location.
     */
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
