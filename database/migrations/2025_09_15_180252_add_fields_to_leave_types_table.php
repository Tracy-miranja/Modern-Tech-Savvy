<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('allows_backdating')->default(false);
            $table->unsignedInteger('approval_levels')->default(1);
            $table->json('excluded_days')->nullable();
            $table->boolean('is_stepwise')->default(false);
            $table->json('stepwise_rules')->nullable();
        });
    }

    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn([
                'allows_backdating',
                'approval_levels',
                'excluded_days',
                'is_stepwise',
                'stepwise_rules',
            ]);
        });
    }

};

