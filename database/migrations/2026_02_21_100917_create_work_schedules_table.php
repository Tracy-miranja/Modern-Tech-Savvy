<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Days of week (0 = Sunday, 6 = Saturday)
            $table->json('working_days'); // e.g., [1,2,3,4,5] for Mon-Fri
            
            // Effective dates
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['employee_id', 'effective_from', 'effective_to']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_schedules');
    }
};