<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Learning Management module (GUIDE follow-up: the last of the 3 modules
 * that previously existed only as a name/price entry in ModulesSeeder).
 * status is a plain string, not a DB enum - see the case_type-enum friction
 * hit earlier in the Disciplinary module, and Asset's status/condition
 * columns following the same fix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('provider')->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->string('status')->default('active'); // draft, active, archived
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
