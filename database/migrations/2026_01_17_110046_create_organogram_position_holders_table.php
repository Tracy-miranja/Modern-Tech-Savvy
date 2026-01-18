<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('organogram_position_holders', function (Blueprint $table) {
    $table->id();

    // MATCH EXACTLY (NO foreignId)
    $table->unsignedBigInteger('organogram_position_id');
    $table->foreign('organogram_position_id')
          ->references('id')
          ->on('organogram_positions')
          ->cascadeOnDelete();

    // MATCH personnel_employee.id (INT)
    $table->integer('employee_id');
    $table->foreign('employee_id')
          ->references('id')
          ->on('personnel_employee')
          ->cascadeOnDelete();

    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->boolean('is_primary')->default(true);
    $table->timestamps();

    $table->unique(
        ['organogram_position_id', 'employee_id', 'start_date'],
        'unique_position_holder'
    );
});

    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_position_holders');
    }
};
