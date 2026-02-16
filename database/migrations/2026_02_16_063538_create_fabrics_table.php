<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30);
            $table->string('collection')->nullable();
            $table->string('opacity'); // sheer, light_filtering, room_darkening, blackout
            $table->string('color');
            $table->string('color_hex', 7)->nullable();
            $table->decimal('price_modifier', 5, 2)->default(0);
            $table->string('price_group', 10)->default('A');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['supplier_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabrics');
    }
};
