<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Documents a column that already exists on production/dev databases
     * (added out-of-band, never tracked by a migration) so a fresh
     * install ends up with the same structure. Guarded to stay a no-op
     * where the column is already present.
     */
    public function up(): void
    {
        if (Schema::hasColumn('employment_details', 'status')) {
            return;
        }

        // enum() via raw SQL: doctrine/dbal (required by Blueprint::enum on some drivers
        // for introspection) isn't installed in this project; raw ALTER is dependency-free.
        DB::statement("ALTER TABLE employment_details ADD COLUMN status ENUM('active','inactive','terminated') NOT NULL DEFAULT 'active' AFTER employment_term");
    }

    public function down(): void
    {
        if (Schema::hasColumn('employment_details', 'status')) {
            Schema::table('employment_details', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
