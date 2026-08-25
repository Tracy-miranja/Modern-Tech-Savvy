<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->foreignId('stage_type_id')->nullable()->after('case_type')
                ->constrained('disciplinary_stage_types')->nullOnDelete();
            $table->timestamp('response_due_at')->nullable()->after('resolution_notes');
            $table->text('employee_response')->nullable()->after('response_due_at');
            $table->timestamp('employee_responded_at')->nullable()->after('employee_response');
        });
    }

    public function down(): void
    {
        Schema::table('warnings', function (Blueprint $table) {
            $table->dropForeign(['stage_type_id']);
            $table->dropColumn(['stage_type_id', 'response_due_at', 'employee_response', 'employee_responded_at']);
        });
    }
};
