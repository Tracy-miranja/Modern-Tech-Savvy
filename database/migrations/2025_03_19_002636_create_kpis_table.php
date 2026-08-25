<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpisTable extends Migration
{
    public function up()
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model_type');
            $table->text('description')->nullable();
            $table->string('calculation_method');
            $table->string('target_value');
            $table->string('comparison_operator');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpis');
    }
}