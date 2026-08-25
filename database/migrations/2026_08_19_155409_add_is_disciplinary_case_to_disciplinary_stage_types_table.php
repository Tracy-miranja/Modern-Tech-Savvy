<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('disciplinary_stage_types', function (Blueprint $table) {
            $table->boolean('is_disciplinary_case')->default(false)->after('requires_response');
        });
    }

    public function down(): void
    {
        Schema::table('disciplinary_stage_types', function (Blueprint $table) {
            $table->dropColumn('is_disciplinary_case');
        });
    }
};
