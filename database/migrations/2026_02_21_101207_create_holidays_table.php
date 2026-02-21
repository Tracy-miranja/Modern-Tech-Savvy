<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->date('date');
            $table->boolean('is_recurring')->default(false); // repeats annually
            $table->boolean('is_working_day')->default(false); // if true, working this day = overtime
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['business_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};