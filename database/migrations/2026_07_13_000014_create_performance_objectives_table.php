<?php

use App\Models\Business;
use App\Models\Employee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(100);
            $table->enum('status', ['on_track', 'at_risk', 'off_track', 'completed'])->default('on_track');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_objectives');
    }
};
