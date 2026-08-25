<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('leave_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->foreignId('employee_id')->constrained();
            $table->foreignId('delegate_id')->constrained('employees');
            $table->foreignId('leave_request_id')->constrained();
            $table->text('duties_delegated');
            $table->boolean('delegate_accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_delegations');
    }
};
