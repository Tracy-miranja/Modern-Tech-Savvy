<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_employee_code_unique');
            $table->dropUnique('employees_national_id_unique');
            $table->dropUnique('employees_tax_no_unique');
            $table->dropUnique('employees_nhif_no_unique');
            $table->dropUnique('employees_nssf_no_unique');
            $table->dropUnique('employees_passport_no_unique');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unique(['business_id', 'employee_code']);
            $table->unique(['business_id', 'national_id']);
            $table->unique(['business_id', 'tax_no']);
            $table->unique(['business_id', 'nhif_no']);
            $table->unique(['business_id', 'nssf_no']);
            $table->unique(['business_id', 'passport_no']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'employee_code']);
            $table->dropUnique(['business_id', 'national_id']);
            $table->dropUnique(['business_id', 'tax_no']);
            $table->dropUnique(['business_id', 'nhif_no']);
            $table->dropUnique(['business_id', 'nssf_no']);
            $table->dropUnique(['business_id', 'passport_no']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unique('employee_code');
            $table->unique('national_id');
            $table->unique('tax_no');
            $table->unique('nhif_no');
            $table->unique('nssf_no');
            $table->unique('passport_no');
        });
    }
};
