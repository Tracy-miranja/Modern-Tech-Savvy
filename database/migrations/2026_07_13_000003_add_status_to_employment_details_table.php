<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('employment_details', 'status')) {
            return;
        }

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
