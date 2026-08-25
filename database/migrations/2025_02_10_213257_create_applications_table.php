<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained();
            $table->foreignId('job_post_id')->constrained();
            $table->longText('cover_letter')->nullable();
            $table->string('stage')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->integer('match_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
