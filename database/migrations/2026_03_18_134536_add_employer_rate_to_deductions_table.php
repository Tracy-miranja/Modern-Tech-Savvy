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
            // Employer's separate contribution rate (%).
            // NULL means "use the same rate as the employee" (backwards-compatible).
            $table->decimal('employer_rate', 8, 4)->nullable()->after('rate');

            // Employer's monthly cap (e.g. 30,000).
            // NULL means "use the same limit as the employee".
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
