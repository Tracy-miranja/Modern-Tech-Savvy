<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('employees', 'manager_override')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('manager_override')->default(false)->after('manager_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'manager_override')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('manager_override');
            });
        }
    }
};
