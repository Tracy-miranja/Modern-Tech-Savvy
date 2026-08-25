<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->onDelete('cascade');
            $table->foreignId('interviewer_id')->constrained('users')->onDelete('cascade');
            $table->text('comments');
            $table->integer('score')->default(0);
            $table->enum('recommendation', ['hire', 'reject', 'second_interview'])->default('second_interview');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedback');
    }
};
