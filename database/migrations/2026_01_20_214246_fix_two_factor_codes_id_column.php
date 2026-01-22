<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('two_factor_codes', function (Blueprint $table) {
            // Drop the existing id if needed (adjust if it has data/constraints)
            $table->dropColumn('id');
            // Add proper auto-increment primary key
            $table->id()->first();
        });
    }

    public function down(): void
    {
        Schema::table('two_factor_codes', function (Blueprint $table) {
            $table->dropColumn('id');
            // Revert to original (adjust as per your original schema)
            $table->bigInteger('id')->unsigned();
        });
    }
};
