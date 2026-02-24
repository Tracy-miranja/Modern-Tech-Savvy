<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('reliefs', function (Blueprint $table) {
        $table->enum('computation_method', ['fixed', 'percentage'])
              ->default('fixed')
              ->after('slug');

        // $table->enum('percentage_of', ['total_salary', 'basic_salary', 'net_salary'])
        //       ->nullable()
        //       ->after('percentage_of_amount');
    });
}

public function down()
{
    // Schema::table('reliefs', function (Blueprint $table) {
    //     $table->dropColumn(['computation_method', 'percentage_of']);
    // });
}
};
