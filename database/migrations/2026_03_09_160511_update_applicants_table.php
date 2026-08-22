<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Step 1: Drop existing foreign key (if any) and make user_id nullable
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

        // Step 2: Clean up orphaned rows BEFORE adding the constraint.
        // This is a data statement, run outside the Schema::table closure,
        // and must happen after dropping the old FK but before adding the new one.
        DB::statement("
            UPDATE applicants
            SET user_id = NULL
            WHERE user_id IS NOT NULL
            AND user_id NOT IN (SELECT id FROM users)
        ");

        // Step 3: Add the new foreign key and any new columns
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

            // Personal details (Part 1)
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
                $table->string('gender', 20)->nullable()->after('phone'); // male/female/other/prefer_not_say
            }

            if (!Schema::hasColumn('applicants', 'dob')) {
                $table->date('dob')->nullable()->after('gender');
            }

            if (!Schema::hasColumn('applicants', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('dob'); // optional snapshot
            }

            if (!Schema::hasColumn('applicants', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('age');
            }

            if (!Schema::hasColumn('applicants', 'plwd')) {
                $table->boolean('plwd')->default(false)->after('nationality');
            }

            // optional: ensure created_by is indexed (helps your filtering)
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
            // Drop columns if they exist
            foreach (['plwd', 'nationality', 'age', 'dob', 'gender', 'phone', 'email', 'id_number', 'full_name'] as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }

            // Revert user_id to NOT NULL if you want (optional)
            // Warning: only do this if you are sure no rows have null user_id
            // try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
            // $table->unsignedBigInteger('user_id')->nullable(false)->change();
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
