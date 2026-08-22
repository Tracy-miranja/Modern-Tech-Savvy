<?php

use App\Models\Business;
use App\Models\Employee;
use App\Models\Warning;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_investigations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Warning::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class, 'investigator_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('started_at')->nullable();
            $table->date('concluded_at')->nullable();
            $table->text('findings')->nullable();
            $table->string('outcome')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_investigations');
    }
};
