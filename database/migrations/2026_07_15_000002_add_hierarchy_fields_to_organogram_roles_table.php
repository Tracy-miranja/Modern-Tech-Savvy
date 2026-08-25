<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('organogram_roles', 'reports_to_role_id')) {
            return;
        }

        Schema::table('organogram_roles', function (Blueprint $table) {
            $table->foreignId('reports_to_role_id')->nullable()->after('level')
                ->constrained('organogram_roles')->nullOnDelete();
            $table->string('spatie_role_name')->nullable()->after('reports_to_role_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('organogram_roles', 'reports_to_role_id')) {
            return;
        }

        Schema::table('organogram_roles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reports_to_role_id');
            $table->dropColumn('spatie_role_name');
        });
    }
};
