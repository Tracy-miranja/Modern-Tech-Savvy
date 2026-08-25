<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up(): void
{
    Schema::table('employees', function (Blueprint $table) {
        $table->boolean('is_exempt_from_payroll')->default(0)->after('blood_group');
        $table->string('resident_status', 50)->nullable()->after('is_exempt_from_payroll');
        $table->string('kra_employee_status', 50)->nullable()->after('resident_status');
    });
}

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

        });
    }
};
