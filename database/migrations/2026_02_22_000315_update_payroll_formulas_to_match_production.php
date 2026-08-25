<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

   public function up()
{
    Schema::table('payroll_formulas', function (Blueprint $table) {

        $table->enum('formula_type', ['rate', 'fixed', 'progressive', 'expression'])
              ->nullable()->change();

        $table->text('description')->nullable()->after('slug');
        $table->boolean('is_statutory')->default(0)->after('is_progressive');
        $table->decimal('limit', 15, 2)->nullable()->after('minimum_amount');
        $table->enum('round_off', ['round_up', 'round_down', 'nearest'])->nullable()->after('limit');
        $table->enum('applies_to', ['all', 'specific'])->default('all')->after('round_off');
        $table->text('expression')->nullable()->after('applies_to');
    });
}

public function down()
{
    Schema::table('payroll_formulas', function (Blueprint $table) {
        $table->dropColumn(['description', 'is_statutory', 'limit', 'round_off', 'applies_to', 'expression']);
    });
}
};
