<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asset Management module (GUIDE follow-up: one of the 3 modules that were
 * only ever a name/price entry in ModulesSeeder with no real
 * implementation). status/condition are plain strings, not DB enums - see
 * the case_type-enum friction hit earlier in the Disciplinary module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('asset_tag');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('status')->default('available'); // available, assigned, maintenance, retired
            $table->string('condition')->nullable(); // new, good, fair, poor
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'asset_tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
