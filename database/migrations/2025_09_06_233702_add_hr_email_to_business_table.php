<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (!Schema::hasColumn('businesses', 'email')) {
                $table->string('email')->nullable()->after('company_name');
            }
            $table->string('hr_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (Schema::hasColumn('businesses', 'hr_email')) {
                $table->dropColumn('hr_email');
            }
            if (Schema::hasColumn('businesses', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
