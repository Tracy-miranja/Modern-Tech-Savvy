<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->string('jurisdiction')->nullable();
            $table->string('rule_type');
            $table->decimal('lower_limit', 15, 2)->nullable();
            $table->decimal('upper_limit', 15, 2)->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country', 'rule_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_rules');
    }
};
