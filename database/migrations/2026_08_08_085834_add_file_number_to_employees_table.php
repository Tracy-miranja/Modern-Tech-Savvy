<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional employee file/reference number - same per-business uniqueness
 * convention as national_id/tax_no/nhif_no/nssf_no/passport_no (see
 * 2026_07_23_000001_scope_employee_identity_uniqueness_to_business.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('file_number')->nullable()->after('employee_code');
            $table->unique(['business_id', 'file_number']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'file_number']);
            $table->dropColumn('file_number');
        });
    }
};
