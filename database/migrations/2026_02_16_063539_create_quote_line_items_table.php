<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('fabric_id')->constrained();
            $table->foreignId('control_type_id')->constrained();
            $table->string('room_name')->nullable();
            $table->string('window_name')->nullable();
            $table->decimal('width', 6, 2);
            $table->decimal('height', 6, 2);
            $table->decimal('rounded_width', 6, 2);
            $table->decimal('rounded_height', 6, 2);
            $table->integer('quantity')->default(1);
            $table->string('mount_type')->default('inside');
            $table->json('selected_options')->nullable();
            $table->decimal('grid_cost', 10, 2);
            $table->decimal('control_adder', 10, 2)->default(0);
            $table->decimal('options_adder', 10, 2)->default(0);
            $table->decimal('surcharges', 10, 2)->default(0);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('markup_pct', 5, 2);
            $table->decimal('unit_sell', 10, 2);
            $table->decimal('line_cost', 10, 2);
            $table->decimal('line_sell', 10, 2);
            $table->decimal('line_margin', 10, 2);
            $table->json('pricing_breakdown')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_line_items');
    }
};
