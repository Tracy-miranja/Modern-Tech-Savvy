<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('employees', 'functional_manager_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('functional_manager_id')->nullable()->after('manager_override');
            $table->foreign('functional_manager_id')->references('id')->on('employees')->nullOnDelete();
            $table->index('functional_manager_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'functional_manager_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropForeign(['functional_manager_id']);
                $table->dropColumn('functional_manager_id');
            });
        }
    }
};
