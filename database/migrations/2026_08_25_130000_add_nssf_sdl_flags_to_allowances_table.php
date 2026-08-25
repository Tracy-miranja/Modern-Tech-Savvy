<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('allowances', function (Blueprint $table) {
            $table->boolean('is_nssf_applicable')->default(true)->after('is_taxable');
            $table->boolean('is_sdl_applicable')->default(true)->after('is_nssf_applicable');
        });
    }

    public function down(): void
    {
        Schema::table('allowances', function (Blueprint $table) {
            $table->dropColumn(['is_nssf_applicable', 'is_sdl_applicable']);
        });
    }
};
