<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->string('category'); // roller_shades, cellular_shades, wood_blinds, faux_wood, verticals, romans
            $table->decimal('min_width', 6, 2)->default(12);
            $table->decimal('max_width', 6, 2)->default(144);
            $table->decimal('min_height', 6, 2)->default(12);
            $table->decimal('max_height', 6, 2)->default(120);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->integer('lead_time_days')->default(10);
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
