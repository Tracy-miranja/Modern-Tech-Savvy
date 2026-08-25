<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    if (Schema::hasColumn('reliefs', 'computation_method')) {
        return;
    }

    Schema::table('reliefs', function (Blueprint $table) {
        $table->enum('computation_method', ['fixed', 'percentage'])
              ->default('fixed')
              ->after('slug');

    });
}

public function down()
{

}
};
