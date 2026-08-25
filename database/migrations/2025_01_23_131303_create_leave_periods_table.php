<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('leave_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('accept_applications')->default(true);
            $table->boolean('can_accrue')->default(true);
            $table->boolean('restrict_applications_within_dates')->default(false);
            $table->boolean('archive')->default(false);
            $table->boolean('autocreate')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_periods');
    }
};
