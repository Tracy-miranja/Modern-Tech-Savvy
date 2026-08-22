<?php

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeeContractAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One checklist per termination - auto-created inside
 * EmployeeController::storeContractAction's termination branch (see GUIDE
 * plan Phase 4), so starting the offboarding process is zero extra manual
 * steps. No unique constraint on employee_id: an employee terminated,
 * later rehired, then terminated again legitimately gets a second checklist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(EmployeeContractAction::class, 'contract_action_id')->nullable()->constrained('employee_contract_actions')->nullOnDelete();
            $table->string('status')->default('in_progress'); // in_progress, completed
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_checklists');
    }
};
