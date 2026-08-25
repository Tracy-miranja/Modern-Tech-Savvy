<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        if (Schema::hasColumn('applicants', 'created_at')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('applicants', 'created_at')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
