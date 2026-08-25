<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        $hasPrimaryKey = DB::selectOne("
            SELECT COUNT(*) as cnt
            FROM information_schema.STATISTICS
            WHERE table_schema = DATABASE()
              AND table_name = 'employee_contract_actions'
              AND index_name = 'PRIMARY'
        ");

        if ($hasPrimaryKey->cnt == 0) {
            DB::statement('ALTER TABLE employee_contract_actions MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
        }
    }

    public function down(): void
    {

    }
};
