<?php

use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Department::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(JobCategory::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('expected_hours_per_day', 4, 2)->default(8.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
