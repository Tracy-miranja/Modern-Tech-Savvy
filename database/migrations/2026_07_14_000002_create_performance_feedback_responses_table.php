<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    public function up(): void
    {
        if (Schema::hasTable('performance_feedback_responses')) {
            return;
        }

       Schema::create('performance_feedback_responses', function (Blueprint $table) {
    $table->id();

    $table->foreignId('performance_feedback_request_id');

    $table->foreign(
        'performance_feedback_request_id',
        'perf_feedback_response_request_fk'
    )->references('id')
     ->on('performance_feedback_requests')
     ->cascadeOnDelete();

    $table->unique(
        'performance_feedback_request_id',
        'pf_resp_req_unique'
    );

    $table->json('answers');
    $table->timestamp('submitted_at');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_feedback_responses');
    }
};
