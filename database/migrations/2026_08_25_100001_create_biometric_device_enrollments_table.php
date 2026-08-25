<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_device_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('device_pin');
            $table->timestamps();

            $table->unique(['biometric_device_id', 'device_pin'], 'device_pin_unique');
            $table->unique(['biometric_device_id', 'employee_id'], 'device_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_device_enrollments');
    }
};
