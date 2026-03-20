<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('currency_code', 10);          // e.g. USD, KES, UGX
            $table->string('currency_name', 100);          // e.g. United States Dollar
            $table->string('symbol', 10)->nullable();      // e.g. $, KSh
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_primary')->default(false); // primary/base currency for this business
            $table->enum('rate_mode', ['auto', 'manual'])->default('auto');
            // Rate = how many units of THIS currency equal 1 unit of the PRIMARY currency
            // e.g. primary=KES, currency=USD → rate=0.00775  (1 KES = 0.00775 USD)
            $table->decimal('manual_rate', 20, 8)->nullable();
            // Cached auto rate (updated when user clicks "Refresh" or on payroll run)
            $table->decimal('auto_rate', 20, 8)->nullable();
            $table->timestamp('rate_fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'currency_code']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_currencies');
    }
};
