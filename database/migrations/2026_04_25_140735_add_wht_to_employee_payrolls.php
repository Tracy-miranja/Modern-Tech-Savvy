<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('employee_payrolls', function (Blueprint $table) {
        $table->boolean('is_consultant')->default(false)->after('days_in_month');
        $table->decimal('wht_amount', 15, 2)->default(0)->after('is_consultant');
        $table->decimal('wht_rate', 5, 2)->default(0)->after('wht_amount');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            //
        });
    }
};
