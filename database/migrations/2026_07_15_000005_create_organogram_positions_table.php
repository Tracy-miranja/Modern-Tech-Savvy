<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * A position is "this employee holds this role" - the actual
     * assignment, as opposed to OrganogramRole which is just the role
     * catalog (name + reporting edge). Multiple positions can share the
     * same role (e.g. two different "Manager" positions, each covering a
     * different set of departments), which is why the role itself isn't
     * unique per business - the position + its department/team coverage
     * (see the two pivot tables created alongside this one) is what makes
     * an assignment specific.
     */
    public function up(): void
    {
        if (Schema::hasTable('organogram_positions')) {
            return;
        }

        Schema::create('organogram_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organogram_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_role_id', 'employee_id'], 'organogram_positions_role_employee_unique');
        });

        Schema::create('organogram_position_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organogram_position_id')->constrained('organogram_positions')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_position_id', 'department_id'], 'organogram_position_department_unique');
        });

        Schema::create('organogram_position_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organogram_position_id')->constrained('organogram_positions')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['organogram_position_id', 'team_id'], 'organogram_position_team_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_position_team');
        Schema::dropIfExists('organogram_position_department');
        Schema::dropIfExists('organogram_positions');
    }
};
