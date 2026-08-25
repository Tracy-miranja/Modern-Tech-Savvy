<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        if (Schema::hasTable('application_academics')) {
            return;
        }

        Schema::create('application_academics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('qualification_level', 80);
            $table->string('institution_name', 255);
            $table->string('institution_country', 100)->nullable();
            $table->string('qualification_name', 255);
            $table->string('certificate_number', 100)->nullable();
            $table->unsignedSmallInteger('year_completed')->nullable();
            $table->timestamps();

            $table->index(['application_id']);
        });

        Schema::create('application_work_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('employer_name', 255);
            $table->string('employer_contact', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('job_title', 255);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);

            $table->longText('achievements')->nullable();
            $table->timestamps();

            $table->index(['application_id']);
        });

        Schema::create('application_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('organization_name', 255);
            $table->string('membership_number', 120);
            $table->string('membership_type', 100)->nullable();
            $table->unsignedSmallInteger('year_joined')->nullable();
            $table->timestamps();

            $table->index(['application_id']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('doc_type', 60);

            $table->string('label', 255)->nullable();

            $table->unsignedBigInteger('media_id')->nullable();

            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamps();

            $table->index(['application_id', 'doc_type']);
            $table->index(['media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('application_memberships');
        Schema::dropIfExists('application_work_experiences');
        Schema::dropIfExists('application_academics');
    }
};