<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unique(
                ['business_id', 'nssf_no'],
                'employees_business_nssf_no_unique'
            );

            $table->unique(
                ['business_id', 'passport_no'],
                'employees_business_passport_no_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_business_nssf_no_unique');
            $table->dropUnique('employees_business_passport_no_unique');
        });
    }
};
