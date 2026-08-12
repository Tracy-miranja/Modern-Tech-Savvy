<?php

use App\Models\Business;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company-mandated leave (e.g. a Dec 24-28 office shutdown): the business
 * declares a date range that automatically deducts from every affected
 * employee's balance for a chosen leave type, instead of each employee
 * applying individually. Scope is one of the whole business, specific
 * departments, or specific locations - see MandatoryLeavePeriod::resolveAffectedEmployees().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandatory_leave_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LeaveType::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('scope_type'); // organization|department|location
            $table->json('scope_ids')->nullable(); // department or location ids, per scope_type
            $table->text('notes')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_leave_periods');
    }
};
