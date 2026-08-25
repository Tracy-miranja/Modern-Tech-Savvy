<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
{
    Schema::table('login_attempts', function (Blueprint $table) {
        $table->timestamp('banned_until')->nullable()->after('attempts');
    });
}

public function down()
{
    Schema::table('login_attempts', function (Blueprint $table) {
        $table->dropColumn('banned_until');
    });
}

};
