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
        Schema::table('leave_entitlements', function (Blueprint $table) {
            //$table->json('policy_snapshot')->nullable()->after('carryover_days');
            //$table->timestamp('last_accrued_at')->nullable()->change();
       // });

       // Schema::table('leave_entitlements', function (Blueprint $table) {
       //     $table->unique(['business_id','employee_id','leave_type_id','leave_period_id'], 'uniq_entl_scope');
       // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       // Schema::table('leave_entitlements', function (Blueprint $table) {
          //  $table->dropUnique('uniq_entl_scope');
         //   $table->dropColumn(['policy_snapshot','last_accrued_at']);
      //  });
    }
};
