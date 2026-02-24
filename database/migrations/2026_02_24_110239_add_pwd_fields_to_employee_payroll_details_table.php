<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('employee_payroll_details', function (Blueprint $table) {
        $table->string('pwd_certificate_no')->nullable()->after('has_disability_exemption');
        $table->string('pwd_ncpwd_membership_no')->nullable()->after('pwd_certificate_no');
        $table->decimal('pwd_exemption_limit', 15, 2)->default(150000.00)->after('pwd_ncpwd_membership_no');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payroll_details', function (Blueprint $table) {
            //
        });
    }
};
