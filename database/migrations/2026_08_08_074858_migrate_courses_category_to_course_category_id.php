<?php

use App\Models\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the free-text `category` column with a proper FK to the new
 * business-configurable course_categories table. Safe to do as a straight
 * swap (no backfill needed) - the Learning Management module shipped this
 * same session with zero real course rows yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->foreignId('course_category_id')->nullable()->after('description')
                ->constrained('course_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_category_id');
            $table->string('category')->nullable();
        });
    }
};
