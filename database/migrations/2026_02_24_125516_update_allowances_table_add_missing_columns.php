<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('allowances', function (Blueprint $table) {
        $table->decimal('amount', 15, 2)->nullable()->after('slug');
        $table->decimal('rate', 5, 2)->nullable()->after('amount');
        $table->string('type')->nullable()->after('rate');
        $table->string('calculation_basis')->nullable()->after('type');
        $table->string('applies_to')->nullable()->after('calculation_basis');
    });
}

public function down()
{
    Schema::table('allowances', function (Blueprint $table) {
        $table->dropColumn([
            'amount',
            'rate',
            'type',
            'calculation_basis',
            'applies_to'
        ]);
    });
}
};
