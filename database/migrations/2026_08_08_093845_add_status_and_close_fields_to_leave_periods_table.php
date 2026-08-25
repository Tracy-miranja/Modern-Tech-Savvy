<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->string('period_status')->default('open')->after('is_active');
            $table->timestamp('closed_at')->nullable();
            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn(['period_status', 'closed_at']);
        });
    }
};
