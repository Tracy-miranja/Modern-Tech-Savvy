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

    $table->foreignId('organogram_position_id')
          ->constrained('organogram_positions')
          ->cascadeOnDelete();

    $table->foreignId('employee_id')
          ->constrained('employees')
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
