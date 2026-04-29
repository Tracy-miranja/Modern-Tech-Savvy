<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // This records every WHT payment made
// Like a ledger / receipt book

public function up()
{
    Schema::create('withholding_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->onDelete('cascade');
        $table->foreignId('employee_id')->constrained()->onDelete('cascade');

        // Link to payroll if processed through payroll
        $table->foreignId('payroll_id')->nullable()->constrained()->onDelete('set null');
        $table->foreignId('employee_payroll_id')->nullable()->constrained()->onDelete('set null');

        $table->string('payment_type');    // 'professional_fees' etc
        $table->string('residency');       // 'resident' / 'non_resident'
        $table->decimal('gross_amount', 15, 2);
        $table->decimal('wht_rate', 5, 2); // e.g. 5.00
        $table->decimal('wht_amount', 15, 2);
        $table->decimal('net_amount', 15, 2);

        // Company-covered statutory costs
        $table->decimal('shif_company_cost', 15, 2)->default(0);
        $table->decimal('nssf_company_cost', 15, 2)->default(0);
        $table->decimal('total_company_cost', 15, 2)->default(0);

        $table->date('payment_date');

        // Has this WHT been remitted to KRA?
        $table->boolean('is_remitted')->default(false);
        $table->date('remittance_date')->nullable();

        // KRA WHT certificate number (filled after iTax)
        $table->string('certificate_no')->nullable();

        $table->string('currency')->default('KES');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_rates');
    }
};
