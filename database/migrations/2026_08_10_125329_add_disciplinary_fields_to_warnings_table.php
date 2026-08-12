<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->string('case_id')->nullable()->unique()->after('id');
            $table->string('category')->default('misconduct')->after('business_id');
            $table->text('offence')->nullable()->after('category');
            $table->string('reported_by_name')->nullable()->after('offence');
            $table->string('stage')->default('informal_action')->after('reported_by_name');
            $table->date('hearing_date')->nullable()->after('stage');
            $table->string('decision_outcome')->default('pending')->after('hearing_date');
            $table->string('appeal_status')->nullable()->after('decision_outcome');
        });

        DB::table('warnings')->orderBy('id')->get()->each(function ($warning) {
            DB::table('warnings')->where('id', $warning->id)->update([
                'case_id' => 'c' . Str::lower(Str::random(8)),
                'offence' => $warning->reason,
                'stage'   => $warning->status === 'resolved' ? 'closed' : 'informal_action',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->dropColumn(['case_id', 'category', 'offence', 'reported_by_name', 'stage', 'hearing_date', 'decision_outcome', 'appeal_status']);
        });
    }
};
