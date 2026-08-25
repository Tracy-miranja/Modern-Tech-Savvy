<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

// not the code

public function up()
{
    if (Schema::hasTable('withholding_tax_rates')) {
        return;
    }

    Schema::create('withholding_tax_rates', function (Blueprint $table) {
        $table->id();

        $table->string('payment_type');

        $table->string('label');

        $table->string('residency');

        $table->decimal('rate', 5, 2);

        $table->boolean('is_final_tax')->default(false);

        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('withholding_payments');
    }
};
