<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('job_posts', function (Blueprint $table) {
        if (!Schema::hasColumn('job_posts', 'status')) {
            $table->string('status')->default('draft')->after('created_by');
        }
        if (!Schema::hasColumn('job_posts', 'closing_date')) {
            $table->date('closing_date')->nullable()->after('status');
        }
        if (!Schema::hasColumn('job_posts', 'is_public')) {
            $table->boolean('is_public')->default(false)->after('closing_date');
        }
        if (!Schema::hasColumn('job_posts', 'slug')) {
            $table->string('slug')->unique()->after('is_public');
        }
    });
}

public function down(): void
{
    Schema::table('job_posts', function (Blueprint $table) {
        $table->dropColumn(['status', 'closing_date', 'is_public', 'slug']);
    });
}
};
