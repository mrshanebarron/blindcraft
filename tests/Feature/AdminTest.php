<?php

namespace Tests\Feature;

use App\Models\{Supplier, Product, PricingGrid, CompatibilityRule, Surcharge, RoundingRule};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_loads(): void
    {
        $this->get('/admin')->assertStatus(200);
    }

    public function test_admin_index_shows_supplier_stats(): void
    {
        $this->createFullSupplierSetup();

        $this->get('/admin')
            ->assertStatus(200)
            ->assertSee('Test Supplier');
    }

    public function test_admin_suppliers_page_loads(): void
    {
        $this->get('/admin/suppliers')->assertStatus(200);
    }

    public function test_admin_supplier_detail_loads(): void
    {
        $this->createFullSupplierSetup();

        $this->get("/admin/suppliers/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertSee('Test Supplier')
            ->assertSee('Roller Shade')
            ->assertSee('Solar Screen 3%')
            ->assertSee('Standard Chain');
    }

    public function test_admin_supplier_detail_404_for_missing(): void
    {
        $this->get('/admin/suppliers/999')->assertStatus(404);
    }

    public function test_admin_products_page_loads(): void
    {
        $this->get('/admin/products')->assertStatus(200);
    }

    public function test_admin_products_lists_all(): void
    {
        $this->createFullSupplierSetup();

        $this->get('/admin/products')
            ->assertStatus(200)
            ->assertSee('Roller Shade');
    }

    public function test_admin_pricing_grid_page_loads(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->get("/admin/pricing-grids/{$this->product->id}")
            ->assertStatus(200);
    }

    public function test_admin_pricing_grid_404_for_missing_product(): void
    {
        $this->get('/admin/pricing-grids/999')->assertStatus(404);
    }

    public function test_admin_rules_page_loads(): void
    {
        $this->get('/admin/rules')->assertStatus(200);
    }

    public function test_admin_rules_shows_compatibility_rules(): void
    {
        $this->createFullSupplierSetup();
        $this->createCompatibilityRule();

        $this->get('/admin/rules')
            ->assertStatus(200)
            ->assertSee('not compatible');
    }

    public function test_admin_rules_shows_surcharges(): void
    {
        $this->createFullSupplierSetup();
        $this->createSurcharge();

        $this->get('/admin/rules')
            ->assertStatus(200)
            ->assertSee('OVERSIZE'); // View renders trigger_type uppercased
    }

    public function test_admin_rules_shows_rounding_rules(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule();

        $this->get('/admin/rules')->assertStatus(200);
    }
}
