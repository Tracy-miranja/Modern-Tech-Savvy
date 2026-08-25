<?php

use App\Models\Business;
use App\Models\Location;
use App\Models\Department;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->nullable()->onDelete('cascade');
            $table->foreignIdFor(Location::class)->nullable()->onDelete('cascade');
            $table->foreignIdFor(Department::class)->nullable()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->string('salary_range')->nullable();
            $table->integer('number_of_positions')->default(1);
            $table->enum('employment_type', ['full-time', 'part-time', 'contract', 'internship']);
            $table->string('place')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
