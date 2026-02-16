<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('rounding_method')->default('up'); // up, down, nearest, half_inch
            $table->decimal('rounding_increment', 8, 4)->default(0.5); // round to nearest X inches
            $table->decimal('default_markup_pct', 5, 2)->default(50.00);
            $table->decimal('freight_flat', 8, 2)->nullable();
            $table->decimal('freight_pct', 5, 2)->nullable();
            $table->decimal('freight_free_above', 10, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
