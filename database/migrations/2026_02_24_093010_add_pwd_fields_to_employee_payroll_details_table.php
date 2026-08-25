<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  public function up()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->boolean('pwd_exemption_applied')->default(false)->after('insurance_relief');
        $table->decimal('pwd_exemption_amount', 15, 2)->default(0)->after('pwd_exemption_applied');
    });
}

    public function down(): void
    {
        Schema::table('employee_payroll_details', function (Blueprint $table) {

        });
    }
};
