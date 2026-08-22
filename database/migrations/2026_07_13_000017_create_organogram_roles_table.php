<?php

use App\Models\Business;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Company-defined hierarchy levels (HOD, Supervisor, Line Manager, ...)
     * used by the "main organogram" template in Organization Setup. This is
     * deliberately separate from Spatie's `roles` table (system access
     * roles like business-admin/business-hr) - this models organizational
     * seniority, not system permissions.
     */
    public function up(): void
    {
        Schema::create('organogram_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class);
            $table->string('name');
            $table->unsignedInteger('level'); // 1 = most senior, larger = more junior
            $table->timestamps();

            $table->unique(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_roles');
    }
};
