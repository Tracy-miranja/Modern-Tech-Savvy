<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'organogram_role_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('organogram_role_id')->nullable()->after('manager_id');
            $table->foreign('organogram_role_id')->references('id')->on('organogram_roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'organogram_role_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['organogram_role_id']);
                $table->dropColumn('organogram_role_id');
            });
        }
    }
};
