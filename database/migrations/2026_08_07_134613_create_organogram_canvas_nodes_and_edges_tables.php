<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organogram_canvas_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->enum('node_type', ['department', 'role', 'job_category']);
            $table->unsignedBigInteger('ref_id');
            $table->integer('pos_x')->nullable();
            $table->integer('pos_y')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'node_type', 'ref_id'], 'org_canvas_node_unique');
        });

        Schema::create('organogram_canvas_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Business::class)->constrained()->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('organogram_canvas_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('organogram_canvas_nodes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['from_node_id', 'to_node_id'], 'org_canvas_edge_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organogram_canvas_edges');
        Schema::dropIfExists('organogram_canvas_nodes');
    }
};
