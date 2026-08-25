<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('payroll_formula_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_formula_id')->constrained()->onDelete('cascade');
            $table->decimal('min', 15, 2);
            $table->decimal('max', 15, 2)->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_formula_brackets');
    }
};
