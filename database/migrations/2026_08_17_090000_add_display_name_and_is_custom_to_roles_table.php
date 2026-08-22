<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a business define its own custom roles alongside the 13 fixed
     * platform-seeded ones. `name` stays Spatie's globally-unique internal
     * identifier (custom roles get an auto-generated one, see
     * Role::generateUniqueName()) - `display_name` is what the business
     * actually typed and what the UI always shows. `is_custom` marks a
     * business-created role explicitly, rather than inferring it from
     * business_id (which fixed roles simply leave null).
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('business_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
            if (Schema::hasColumn('roles', 'is_custom')) {
                $table->dropColumn('is_custom');
            }
        });
    }
};
