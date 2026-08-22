<?php

use App\Models\Business;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual payment ledger (user's explicit choice over a real gateway
 * integration - no Stripe/Paystack, no webhooks). A platform admin records
 * that a client paid; recording it extends business_modules.subscription_ends_at
 * for the covered module(s) - see ClientPaymentController::store(). module_id
 * is nullable: null means the payment covers every module the client
 * currently has active, not just one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class, 'client_business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignIdFor(Module::class)->nullable()->constrained('modules')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('payment_method'); // bank, mpesa, cheque, cash, other
            $table->string('reference')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->text('notes')->nullable();
            $table->foreignIdFor(User::class, 'recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
