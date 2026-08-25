<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('industry');
            $table->string('company_size');
            $table->string('phone');
            $table->string('country');
            $table->string('code');
            $table->string('registration_no')->nullable();
            $table->string('tax_pin_no')->nullable();
            $table->string('business_license_no')->nullable();
            $table->string('physical_address')->nullable();
            $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
