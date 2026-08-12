<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $column = collect(DB::select("SHOW COLUMNS FROM employment_details WHERE Field = 'status'"))->first();

        if ($column && str_contains($column->Type, "'suspended'")) {
            return;
        }

        DB::statement("ALTER TABLE employment_details MODIFY status ENUM('active','inactive','terminated','suspended') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employment_details MODIFY status ENUM('active','inactive','terminated') NOT NULL DEFAULT 'active'");
    }
};
