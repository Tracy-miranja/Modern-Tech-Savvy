<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('kpi_weight', 5, 2)->default(40);
            $table->decimal('okr_weight', 5, 2)->default(40);
            $table->decimal('competency_weight', 5, 2)->default(20);
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->date('self_review_due_date')->nullable();
            $table->date('manager_review_due_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_cycles');
    }
};
