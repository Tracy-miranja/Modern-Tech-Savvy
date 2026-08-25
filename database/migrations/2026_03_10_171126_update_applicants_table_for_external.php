<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'user_id')) {
                try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}

                $table->unsignedBigInteger('user_id')->nullable()->change();

                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('applicants', 'dob')) {
                $table->date('dob')->nullable()->after('age');
            }
            if (!Schema::hasColumn('applicants', 'gender')) {
                $table->string('gender', 20)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('applicants', 'plwd')) {
                $table->boolean('plwd')->default(false)->after('country');
            }
            if (!Schema::hasColumn('applicants', 'home_county')) {
                $table->string('home_county', 100)->nullable()->after('city');
            }

            if (Schema::hasColumn('applicants', 'idnumber')) {
                $table->index('idnumber');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach (['home_county','plwd','gender','dob'] as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
