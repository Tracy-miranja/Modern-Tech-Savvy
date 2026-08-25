<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {

        Schema::table('applicants', function (Blueprint $table) {
            $foreignKey = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'applicants'
                AND COLUMN_NAME = 'user_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_SCHEMA = DATABASE()
            ");

            if (!empty($foreignKey)) {
                $table->dropForeign(['user_id']);
            }

            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        DB::statement("
            UPDATE applicants
            SET user_id = NULL
            WHERE user_id IS NOT NULL
            AND user_id NOT IN (SELECT id FROM users)
        ");

        Schema::table('applicants', function (Blueprint $table) {
            $existingForeignKey = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'applicants'
                AND COLUMN_NAME = 'user_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_SCHEMA = DATABASE()
            ");

            if (empty($existingForeignKey)) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('applicants', 'full_name')) {
                $table->string('full_name', 255)->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('applicants', 'id_number')) {
                $table->string('id_number', 50)->nullable()->after('full_name');
            }

            if (!Schema::hasColumn('applicants', 'email')) {
                $table->string('email', 255)->nullable()->after('id_number');
            }

            if (!Schema::hasColumn('applicants', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }

            if (!Schema::hasColumn('applicants', 'gender')) {
                $table->string('gender', 20)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('applicants', 'dob')) {
                $table->date('dob')->nullable()->after('gender');
            }

            if (!Schema::hasColumn('applicants', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('dob');
            }

            if (!Schema::hasColumn('applicants', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('age');
            }

            if (!Schema::hasColumn('applicants', 'plwd')) {
                $table->boolean('plwd')->default(false)->after('nationality');
            }

            $existingIndex = DB::select("
                SELECT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'applicants'
                AND COLUMN_NAME = 'created_by'
                AND TABLE_SCHEMA = DATABASE()
            ");

            if (Schema::hasColumn('applicants', 'created_by') && empty($existingIndex)) {
                $table->index('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {

            foreach (['plwd', 'nationality', 'age', 'dob', 'gender', 'phone', 'email', 'id_number', 'full_name'] as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }

        });
    }
};
