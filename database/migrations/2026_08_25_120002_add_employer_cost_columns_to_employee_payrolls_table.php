<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->decimal('employer_nssf', 15, 2)->nullable()->after('nssf');
            $table->decimal('sdl', 15, 2)->nullable()->after('employer_nssf');
            $table->decimal('wcf', 15, 2)->nullable()->after('sdl');
        });
    }

    public function down(): void
    {
        Schema::table('employee_payrolls', function (Blueprint $table) {
            $table->dropColumn(['employer_nssf', 'sdl', 'wcf']);
        });
    }
};
