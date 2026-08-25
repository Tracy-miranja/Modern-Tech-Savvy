<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('organogram_position_holders');
        Schema::dropIfExists('organogram_positions');
    }

    public function down(): void
    {

    }
};
