<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_grids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('width_min', 6, 2);
            $table->decimal('width_max', 6, 2);
            $table->decimal('height_min', 6, 2);
            $table->decimal('height_max', 6, 2);
            $table->string('price_group', 10)->default('A');
            $table->decimal('dealer_cost', 10, 2);
            $table->timestamps();

            $table->index(['product_id', 'price_group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_grids');
    }
};
