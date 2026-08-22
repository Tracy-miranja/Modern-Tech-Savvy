<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A business can have locations (branches) in different countries -
     * each location's own country (falling back to the business's country
     * when not set) determines which public holidays apply to employees
     * based there. Free-text, matching Business.country's convention (and
     * the same Nager.Date country-name guessing already used for
     * business-wide holiday import).
     */
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
