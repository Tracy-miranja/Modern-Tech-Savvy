<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('leave_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('leave_type_id')->constrained();
            $table->foreignId('leave_period_id')->constrained();
            $table->decimal('carry_forward', 5, 2);
            $table->decimal('entitled_days', 5, 2);
            $table->decimal('accrued_days', 5, 2);
            $table->decimal('total_days', 5, 2);
            $table->decimal('days_taken', 5, 2)->default(0.00);
            $table->decimal('days_remaining', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_entitlements');
    }
};
