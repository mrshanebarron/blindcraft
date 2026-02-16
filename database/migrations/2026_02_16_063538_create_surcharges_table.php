<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surcharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type'); // oversize, undersize, specialty_shape, rush, custom
            $table->decimal('trigger_value', 8, 2)->nullable();
            $table->string('trigger_dimension')->nullable(); // width, height, sqft, united_inches
            $table->string('charge_type'); // flat, percentage, per_sqft, per_united_inch
            $table->decimal('charge_amount', 10, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surcharges');
    }
};
