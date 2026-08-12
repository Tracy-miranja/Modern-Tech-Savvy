<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documents schema that already exists on production/dev databases
     * (created out-of-band, never tracked by a migration) so a fresh
     * install ends up with the same structure. Guarded to stay a no-op
     * where the table is already present.
     */
    public function up(): void
    {
        if (Schema::hasTable('employee_contract_actions')) {
            return;
        }

        Schema::create('employee_contract_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('employee_id');
            $table->enum('action_type', ['termination', 'reminder']);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->date('action_date');
            $table->enum('status', ['active', 'reversed', 'sent']);
            $table->unsignedBigInteger('issued_by_id');
            $table->timestamps();

            $table->index('business_id', 'fk_business_id');
            $table->index('employee_id', 'fk_employee_id');
            $table->index('issued_by_id', 'fk_issued_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contract_actions');
    }
};
