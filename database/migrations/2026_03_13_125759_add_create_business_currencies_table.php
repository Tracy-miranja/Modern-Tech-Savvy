<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_currencies')) {
            return;
        }

        Schema::create('business_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->string('currency_code', 10);
            $table->string('currency_name', 100);
            $table->string('symbol', 10)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_primary')->default(false);
            $table->enum('rate_mode', ['auto', 'manual'])->default('auto');

            $table->decimal('manual_rate', 20, 8)->nullable();

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
