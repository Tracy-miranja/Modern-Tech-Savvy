<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('company_size')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('tax_pin_no')->nullable();
            $table->string('business_license_no')->nullable();
            $table->string('physical_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
