<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organogram_positions', function (Blueprint $table) {
    $table->id(); // BIGINT UNSIGNED

    $table->foreignId('business_id')
          ->constrained('businesses')
          ->cascadeOnDelete();

    $table->string('title', 150);
    $table->string('code', 50)->nullable();

    $table->unsignedBigInteger('parent_id')->nullable();
    $table->foreign('parent_id')
          ->references('id')
          ->on('organogram_positions')
          ->nullOnDelete();

    $table->integer('personnel_position_id')->nullable();
    $table->foreign('personnel_position_id')
          ->references('id')
          ->on('personnel_position')
          ->nullOnDelete();

    $table->unsignedTinyInteger('level')->default(1);
    $table->unsignedSmallInteger('sort_order')->default(999);
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(true);

    $table->timestamps();

    $table->index(['business_id', 'parent_id']);
});

    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_positions');
    }
};
