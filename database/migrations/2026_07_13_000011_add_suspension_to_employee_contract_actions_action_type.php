<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        $column = collect(DB::select("SHOW COLUMNS FROM employee_contract_actions WHERE Field = 'action_type'"))->first();

        if ($column && str_contains($column->Type, "'suspension'")) {
            return;
        }

        DB::statement("ALTER TABLE employee_contract_actions MODIFY action_type ENUM('termination','reminder','suspension') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employee_contract_actions MODIFY action_type ENUM('termination','reminder') NOT NULL");
    }
};
