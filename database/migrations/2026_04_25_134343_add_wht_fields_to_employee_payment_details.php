<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('employee_payment_details', function (Blueprint $table) {
        // Is this person a consultant?
        $table->boolean('is_consultant')->default(false);

        // What type of consultant work?
        // (determines WHT rate)
        $table->string('wht_payment_type')->nullable();
        // Values: 'professional_fees', 'training_fees',
        //         'contractual', 'commissions'

        // Resident or non-resident?
        $table->string('wht_residency')->default('resident');
        // Values: 'resident', 'non_resident'

        // Their KRA PIN (needed for WHT certificate)
        $table->string('wht_pin')->nullable();

        // Does company cover SHIF for them?
        $table->boolean('consultant_shif_covered')->default(false);
        $table->string('consultant_shif_basis')->nullable();
        // Values: 'statutory', 'fixed'
        $table->decimal('consultant_shif_fixed_amount', 12, 2)->nullable();

        // Does company cover NSSF for them?
        $table->boolean('consultant_nssf_covered')->default(false);
        $table->string('consultant_nssf_basis')->nullable();
        $table->decimal('consultant_nssf_fixed_amount', 12, 2)->nullable();
    });
}

public function down()
{
    Schema::table('employee_payment_details', function (Blueprint $table) {
        $table->dropColumn([
            'is_consultant',
            'wht_payment_type',
            'wht_residency',
            'wht_pin',
            'consultant_shif_covered',
            'consultant_shif_basis',
            'consultant_shif_fixed_amount',
            'consultant_nssf_covered',
            'consultant_nssf_basis',
            'consultant_nssf_fixed_amount',
        ]);
    });
}
};
