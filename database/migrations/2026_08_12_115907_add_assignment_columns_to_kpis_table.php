<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('kpis', function (Blueprint $table) {
        $table->foreignId('business_id')->nullable()->after('comparison_operator')->constrained()->nullOnDelete();
        $table->foreignId('location_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
        $table->foreignId('employee_id')->nullable()->after('location_id')->constrained()->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('kpis', function (Blueprint $table) {
        $table->dropForeign(['business_id']);
        $table->dropForeign(['location_id']);
        $table->dropForeign(['employee_id']);
        $table->dropColumn(['business_id', 'location_id', 'employee_id']);
    });
}
};
