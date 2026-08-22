<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not every warning rises to the level of a formal disciplinary case -
     * a business marks the stages that do (e.g. from Final Warning onward)
     * so the "Cases" vs "Warnings" tabs can filter the same underlying
     * warnings table into two different views instead of needing a
     * separate case entity.
     */
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
