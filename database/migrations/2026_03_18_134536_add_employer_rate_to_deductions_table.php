<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasColumn('deductions', 'employer_rate')) {
            return;
        }

        Schema::table('deductions', function (Blueprint $table) {

            $table->decimal('employer_rate', 8, 4)->nullable()->after('rate');

            $table->decimal('employer_limit', 15, 2)->nullable()->after('limit');
        });
    }

    public function down(): void
    {
        Schema::table('deductions', function (Blueprint $table) {
            $table->dropColumn(['employer_rate', 'employer_limit']);
        });
    }
};
