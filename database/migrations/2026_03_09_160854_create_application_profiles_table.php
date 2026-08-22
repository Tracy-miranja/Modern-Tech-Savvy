<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /**
         * Part 2: Academic Experience (multiple)
         */
        if (Schema::hasTable('application_academics')) {
            return;
        }

        Schema::create('application_academics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('qualification_level', 80);      // e.g. Certificate/Diploma/Bachelors/Masters/PhD
            $table->string('institution_name', 255);
            $table->string('institution_country', 100)->nullable();
            $table->string('qualification_name', 255);      // e.g. BSc Computer Science
            $table->string('certificate_number', 100)->nullable();
            $table->unsignedSmallInteger('year_completed')->nullable(); // 1900-2100
            $table->timestamps();

            $table->index(['application_id']);
        });

        /**
         * Part 3: Work Experience (multiple)
         */
        Schema::create('application_work_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('employer_name', 255);
            $table->string('employer_contact', 255)->nullable(); // phone/email
            $table->string('location', 255)->nullable();
            $table->string('job_title', 255);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);

            $table->longText('achievements')->nullable(); // achievements & responsibilities
            $table->timestamps();

            $table->index(['application_id']);
        });

        /**
         * Part 4: Professional Memberships (multiple)
         */
        Schema::create('application_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            $table->string('organization_name', 255);
            $table->string('membership_number', 120); // required
            $table->string('membership_type', 100)->nullable(); // select
            $table->unsignedSmallInteger('year_joined')->nullable();
            $table->timestamps();

            $table->index(['application_id']);
        });

        /**
         * Part 5: Documents (CV, National ID, Academic certs, Membership cert, Others)
         * This table references Spatie Media (media.id) if you use MediaLibrary.
         * If you don't want to store media_id, you can store path/file_name instead.
         */
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnDelete();

            // cv, national_id, academic_attachment, membership_certificate, other
            $table->string('doc_type', 60);

            // Optional labeling (e.g. "Bachelor Transcript", "KRA Pin", etc.)
            $table->string('label', 255)->nullable();

            // If using Spatie media library:
            $table->unsignedBigInteger('media_id')->nullable();

            // If not using Spatie, you can store these too (optional):
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