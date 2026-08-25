<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('employees', 'manager_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('manager_id')->nullable()->after('department_id');
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
            $table->index('manager_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'manager_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['manager_id']);
                $table->dropColumn('manager_id');
            });
        }
    }
};
