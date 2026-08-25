<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('project_task_categories', function (Blueprint $table) {
            $table->unsignedInteger('sequence_order')->default(0)->after('color');
        });

        DB::table('project_task_categories')
            ->orderBy('id')
            ->select('id', 'business_id')
            ->get()
            ->groupBy('business_id')
            ->each(function ($rows) {
                $order = 1;
                foreach ($rows as $row) {
                    DB::table('project_task_categories')->where('id', $row->id)->update(['sequence_order' => $order++]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('project_task_categories', function (Blueprint $table) {
            $table->dropColumn('sequence_order');
        });
    }
};
