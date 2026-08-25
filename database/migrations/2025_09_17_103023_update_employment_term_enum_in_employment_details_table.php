<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up(): void
    {
        DB::statement("ALTER TABLE employment_details MODIFY COLUMN employment_term ENUM('contract', 'fulltime', 'permanent', 'Consultant', 'internship', 'locum')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employment_details MODIFY COLUMN employment_term ENUM('contract', 'fulltime', 'permanent', 'consultant', 'internship', 'locum','temporary')");
    }
};
