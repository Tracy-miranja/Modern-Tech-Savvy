<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of "Year Open/Close": lets a LeavePeriod be explicitly closed,
 * blocking new leave requests dated within it and triggering carryover
 * into whichever period immediately follows it - see
 * LeavePeriodController::close(). Deliberately scoped to LeavePeriod only,
 * not a business-wide fiscal year - see the plan's docblock for why the
 * full system-wide version is a separate, much larger future initiative.
 *
 * Named `period_status`, NOT `status` - LeavePeriod already uses Spatie's
 * HasStatuses trait (a `status()` METHOD, set via setStatus(Status::ACTIVE)
 * in LeavePeriodController::store()) for an unrelated active/inactive
 * concept, and a literal `status` column would shadow/collide with that
 * existing mechanism via Eloquent's magic property resolution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->string('period_status')->default('open')->after('is_active'); // open, closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn(['period_status', 'closed_at']);
        });
    }
};
