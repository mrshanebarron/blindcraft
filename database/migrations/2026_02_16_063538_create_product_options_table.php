<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->string('group')->default('upgrade'); // upgrade, mount, specialty
            $table->decimal('price_adder', 8, 2)->default(0);
            $table->decimal('price_pct', 5, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['supplier_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
