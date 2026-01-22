<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('roles', function (Blueprint $table) {
        $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
        // or $table->unsignedBigInteger('business_id')->nullable(); + index/foreign key
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            //
        });
    }
};
