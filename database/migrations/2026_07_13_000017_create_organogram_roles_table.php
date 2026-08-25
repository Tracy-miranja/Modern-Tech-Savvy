<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('organogram_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->string('name');
            $table->unsignedInteger('level');
            $table->timestamps();

            $table->unique(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_roles');
    }
};
