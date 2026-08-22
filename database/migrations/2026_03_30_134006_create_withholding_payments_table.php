<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // This table stores all WHT rates
// So when KRA changes rates, you just update the DB
// not the code

public function up()
{
    if (Schema::hasTable('withholding_tax_rates')) {
        return;
    }

    Schema::create('withholding_tax_rates', function (Blueprint $table) {
        $table->id();

        // e.g. 'professional_fees', 'contractual'
        $table->string('payment_type');

        // Human readable e.g. 'Professional / Consultancy Fees'
        $table->string('label');

        // 'resident' or 'non_resident'
        $table->string('residency');

        // e.g. 5.00 means 5%
        $table->decimal('rate', 5, 2);

        // Is this the final tax or advance?
        // Resident = advance (creditable), Non-resident = final
        $table->boolean('is_final_tax')->default(false);

        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_payments');
    }
};
