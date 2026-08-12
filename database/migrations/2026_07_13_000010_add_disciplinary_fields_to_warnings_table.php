<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evolves the flat "warnings" log into a full disciplinary case record:
     * a case type/stage (verbal -> written -> final -> suspension ->
     * termination), severity, an escalation chain (previous_case_id links a
     * case to the one it escalated from, replacing the old hardcoded
     * "max 2 warnings" cap), employee acknowledgement, resolution notes, and
     * an attachment - without renaming the existing table/model, since many
     * views/routes/mailables already depend on the `Warning` name.
     */
    public function up(): void
    {
        if (Schema::hasColumn('warnings', 'case_type')) {
            return;
        }

        Schema::table('warnings', function (Blueprint $table) {
            $table->enum('case_type', ['verbal_warning', 'written_warning', 'final_warning', 'suspension', 'termination'])
                ->default('written_warning')
                ->after('employee_id');
            $table->enum('severity', ['low', 'medium', 'high'])->default('medium')->after('case_type');
            $table->unsignedBigInteger('previous_case_id')->nullable()->after('severity');
            $table->string('attachment')->nullable()->after('description');
            $table->timestamp('acknowledged_at')->nullable()->after('status');
            $table->unsignedBigInteger('acknowledged_by')->nullable()->after('acknowledged_at');
            $table->text('resolution_notes')->nullable()->after('acknowledged_by');

            $table->foreign('previous_case_id')->references('id')->on('warnings')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('warnings', 'case_type')) {
            return;
        }

        Schema::table('warnings', function (Blueprint $table) {
            $table->dropForeign(['previous_case_id']);
            $table->dropForeign(['acknowledged_by']);
            $table->dropColumn([
                'case_type', 'severity', 'previous_case_id', 'attachment',
                'acknowledged_at', 'acknowledged_by', 'resolution_notes',
            ]);
        });
    }
};
