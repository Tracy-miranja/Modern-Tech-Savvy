<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->unsignedBigInteger('business_id');
            $table->string('email');
            $table->string('registration_token');
            $table->timestamps();

            $table->unique(['requester_id', 'business_id', 'registration_token'], 'unique_req_bus_token');

            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
