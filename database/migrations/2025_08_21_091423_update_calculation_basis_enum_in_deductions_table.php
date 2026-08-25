<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {

        DB::statement("ALTER TABLE deductions MODIFY COLUMN calculation_basis ENUM('basic_pay', 'gross_pay', 'cash_pay', 'taxable_pay', 'custom') NOT NULL");
    }

    public function down(): void
    {

        DB::statement("ALTER TABLE deductions MODIFY COLUMN calculation_basis ENUM('basic_pay', 'gross_pay', 'cash_pay', 'taxable_pay') NOT NULL");
    }
};
