<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compatibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rule_type'); // requires, excludes, max_size_with, min_size_with
            $table->string('subject_type'); // fabric, control_type, option
            $table->unsignedBigInteger('subject_id');
            $table->string('target_type'); // fabric, control_type, option, dimension
            $table->unsignedBigInteger('target_id')->nullable();
            $table->decimal('target_value', 8, 2)->nullable();
            $table->string('message');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compatibility_rules');
    }
};
