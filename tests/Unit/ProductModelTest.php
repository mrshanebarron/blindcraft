<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_dimensions_all_valid(): void
    {
        $this->createFullSupplierSetup();

        $errors = $this->product->validateDimensions(36, 48);
        $this->assertEmpty($errors);
    }

    public function test_validate_dimensions_width_too_small(): void
    {
        $this->createFullSupplierSetup(['product' => ['min_width' => 12]]);

        $errors = $this->product->validateDimensions(8, 48);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('at least 12', $errors[0]);
    }

    public function test_validate_dimensions_width_too_large(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144]]);

        $errors = $this->product->validateDimensions(150, 48);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('exceed 144', $errors[0]);
    }

    public function test_validate_dimensions_height_too_small(): void
    {
        $this->createFullSupplierSetup(['product' => ['min_height' => 12]]);

        $errors = $this->product->validateDimensions(36, 6);
        $this->assertNotEmpty($errors);
    }

    public function test_validate_dimensions_height_too_large(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_height' => 120]]);

        $errors = $this->product->validateDimensions(36, 130);
        $this->assertNotEmpty($errors);
    }

    public function test_validate_dimensions_multiple_errors(): void
    {
        $this->createFullSupplierSetup(['product' => ['min_width' => 12, 'max_height' => 120]]);

        $errors = $this->product->validateDimensions(8, 130);
        $this->assertCount(2, $errors);
    }

    public function test_validate_dimensions_at_exact_boundary(): void
    {
        $this->createFullSupplierSetup([
            'product' => ['min_width' => 12, 'max_width' => 144, 'min_height' => 12, 'max_height' => 120],
        ]);

        $this->assertEmpty($this->product->validateDimensions(12, 12));
        $this->assertEmpty($this->product->validateDimensions(144, 120));
    }

    public function test_active_scope(): void
    {
        $this->createFullSupplierSetup();
        Product::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Inactive',
            'code' => 'INA',
            'category' => 'roller_shades',
            'active' => false,
        ]);

        $this->assertCount(1, Product::active()->get());
    }

    public function test_supplier_relationship(): void
    {
        $this->createFullSupplierSetup();

        $this->assertEquals($this->supplier->id, $this->product->supplier->id);
        $this->assertEquals('Test Supplier', $this->product->supplier->name);
    }
}
