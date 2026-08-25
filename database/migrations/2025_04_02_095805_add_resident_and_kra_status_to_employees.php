<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResidentAndKraStatusToEmployees extends Migration
{

    // }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('resident_status');
            $table->dropColumn('kra_employee_status');
        });
    }
}
