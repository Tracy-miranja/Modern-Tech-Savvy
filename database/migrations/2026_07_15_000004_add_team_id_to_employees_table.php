<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'team_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('department_id')
                ->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'team_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
