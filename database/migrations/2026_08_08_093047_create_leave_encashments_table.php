<?php

use App\Models\Business;
use App\Models\Employee;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * On-demand leave encashment (cash out unused leave) - deliberately
 * Leave-side only, no Payroll integration. `daily_rate`/`amount` are
 * computed and stored at request time as a read-only snapshot off the
 * employee's existing payment fields, not a payroll calculation. `status`
 * goes pending -> approved/rejected -> disbursed, where "disbursed" is a
 * manual HR acknowledgement that Payroll paid it outside this flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_encashments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LeaveType::class)->constrained('leave_types')->cascadeOnDelete();
            $table->foreignIdFor(LeavePeriod::class)->nullable()->constrained('leave_periods')->nullOnDelete();
            $table->decimal('days_requested', 6, 2);
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending, approved, rejected, disbursed
            $table->timestamp('requested_at')->nullable();
            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->text('disbursed_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_encashments');
    }
};
