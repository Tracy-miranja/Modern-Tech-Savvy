<?php

use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\MandatoryLeavePeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit/reversal trail for MandatoryLeavePeriod: one row per employee whose
 * LeaveEntitlement.adjustment_days was decremented when the period was
 * applied. Needed because LeaveEntitlement.adjustment_days is a single
 * cumulative number shared with manual HR adjustments - to correctly claw
 * back exactly this period's own contribution (on edit/delete) without
 * touching any other adjustment on the same entitlement, we have to
 * remember exactly how many days this period deducted per employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandatory_leave_period_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MandatoryLeavePeriod::class)
                ->constrained(indexName: 'mlp_deductions_period_id_fk')
                ->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LeaveEntitlement::class)->constrained()->cascadeOnDelete();
            $table->decimal('days_deducted', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_leave_period_deductions');
    }
};
