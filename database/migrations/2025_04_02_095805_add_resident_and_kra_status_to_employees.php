<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidentAndKraStatusToEmployees extends Migration
{
    // public function up()
    // {
    //     Schema::table('employees', function (Blueprint $table) {
    //         // $table->string('resident_status')->nullable()->after('is_exempt_from_payroll'); // e.g., "Resident", "Non-Resident"
    //         $table->enum('kra_employee_status', ['Primary Employee', 'Secondary Employee'])->nullable()->after('resident_status');
    //     });
    // }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('resident_status');
            $table->dropColumn('kra_employee_status');
        });
    }
}
