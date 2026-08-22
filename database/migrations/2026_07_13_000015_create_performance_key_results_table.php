<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_key_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_objective_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('target_value', 12, 2)->default(100);
            $table->decimal('current_value', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_key_results');
    }
};
