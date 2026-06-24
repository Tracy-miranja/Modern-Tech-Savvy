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
    Schema::table('applicants', function (Blueprint $table) {
        if (!Schema::hasColumn('applicants', 'fullname')) {
            $table->string('fullname')->nullable()->after('user_id');
        }
        if (!Schema::hasColumn('applicants', 'dob')) {
            $table->date('dob')->nullable()->after('age');
        }
        if (!Schema::hasColumn('applicants', 'gender')) {
            $table->string('gender')->nullable()->after('dob');
        }
        if (!Schema::hasColumn('applicants', 'plwd')) {
            $table->boolean('plwd')->default(false)->after('pwd');
        }
        if (!Schema::hasColumn('applicants', 'home_county')) {
            $table->string('home_county')->nullable()->after('city');
        }
        if (!Schema::hasColumn('applicants', 'education_level')) {
            $table->string('education_level')->nullable()->after('academic_level');
        }
        if (!Schema::hasColumn('applicants', 'desired_salary')) {
            $table->decimal('desired_salary', 10, 2)->nullable()->after('salary_expectation');
        }
        if (!Schema::hasColumn('applicants', 'current_job_title')) {
            $table->string('current_job_title')->nullable();
        }
        if (!Schema::hasColumn('applicants', 'current_company')) {
            $table->string('current_company')->nullable();
        }
        if (!Schema::hasColumn('applicants', 'summary')) {
            $table->text('summary')->nullable();
        }
    });
}

public function down(): void
{
    Schema::table('applicants', function (Blueprint $table) {
        $table->dropColumn([
            'fullname', 'dob', 'gender', 'plwd', 'home_county',
            'education_level', 'desired_salary', 'current_job_title',
            'current_company', 'summary',
        ]);
    });
}
};
