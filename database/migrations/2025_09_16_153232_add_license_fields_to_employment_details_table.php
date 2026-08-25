<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

  public function up()
    {
        Schema::table('employment_details', function (Blueprint $table) {
            $table->string('license_reg_number')->nullable()->after('job_description');
            $table->date('license_expiry_date')->nullable()->after('license_reg_number');
        });
    }

    public function down()
    {
        Schema::table('employment_details', function (Blueprint $table) {
            $table->dropColumn(['license_reg_number', 'license_expiry_date']);
        });
    }
};
