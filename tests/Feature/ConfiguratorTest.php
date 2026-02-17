<?php

namespace Tests\Feature;

use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_configurator_page_loads(): void
    {
        $this->get('/configure')->assertStatus(200);
    }

    public function test_configurator_shows_active_suppliers(): void
    {
        $this->createFullSupplierSetup();

        $this->get('/configure')
            ->assertStatus(200)
            ->assertSee('Test Supplier');
    }

    public function test_configurator_hides_inactive_suppliers(): void
    {
        $this->createFullSupplierSetup(['supplier' => ['active' => false]]);

        $this->get('/configure')
            ->assertStatus(200)
            ->assertDontSee('Test Supplier');
    }

    // ── API: Products by Supplier ──────────────────────────────────

    public function test_api_products_returns_json(): void
    {
        $this->createFullSupplierSetup();

        $this->getJson("/api/products/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Roller Shade']);
    }

    public function test_api_products_excludes_inactive(): void
    {
        $this->createFullSupplierSetup();
        Product::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Inactive Product',
            'code' => 'INACT',
            'category' => 'roller_shades',
            'active' => false,
        ]);

        $this->getJson("/api/products/{$this->supplier->id}")
            ->assertJsonCount(1)
            ->assertJsonMissing(['name' => 'Inactive Product']);
    }

    public function test_api_products_404_for_missing_supplier(): void
    {
        $this->getJson('/api/products/999')->assertStatus(404);
    }

    // ── API: Fabrics by Supplier ───────────────────────────────────

    public function test_api_fabrics_returns_json(): void
    {
        $this->createFullSupplierSetup();

        $this->getJson("/api/fabrics/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Solar Screen 3%']);
    }

    public function test_api_fabrics_excludes_inactive(): void
    {
        $this->createFullSupplierSetup();
        Fabric::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Dead Fabric',
            'code' => 'DEAD',
            'opacity' => 'blackout',
            'color' => 'Gray',
            'active' => false,
        ]);

        $this->getJson("/api/fabrics/{$this->supplier->id}")
            ->assertJsonCount(1)
            ->assertJsonMissing(['name' => 'Dead Fabric']);
    }

    // ── API: Controls by Supplier ──────────────────────────────────

    public function test_api_controls_returns_json(): void
    {
        $this->createFullSupplierSetup();

        $this->getJson("/api/controls/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Standard Chain']);
    }

    // ── API: Options by Supplier ───────────────────────────────────

    public function test_api_options_returns_json(): void
    {
        $this->createFullSupplierSetup();
        $this->createOption();

        $this->getJson("/api/options/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Cordless Upgrade']);
    }

    public function test_api_options_empty_when_none(): void
    {
        $this->createFullSupplierSetup();

        $this->getJson("/api/options/{$this->supplier->id}")
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    // ── API: Calculate ─────────────────────────────────────────────

    public function test_calculate_returns_pricing(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->postJson('/api/calculate', $this->validCalculatePayload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'pricing' => [
                    'rounded_width', 'rounded_height', 'grid_cost',
                    'control_adder', 'options_adder', 'surcharges',
                    'unit_cost', 'markup_pct', 'unit_sell',
                    'quantity', 'line_cost', 'line_sell', 'line_margin', 'margin_pct',
                ],
                'breakdown' => ['input', 'steps', 'errors', 'warnings'],
            ]);
    }

    public function test_calculate_validates_required_fields(): void
    {
        $this->postJson('/api/calculate', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'fabric_id', 'control_type_id', 'width', 'height']);
    }

    public function test_calculate_validates_width_range(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->postJson('/api/calculate', $this->validCalculatePayload(['width' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['width']);

        $this->postJson('/api/calculate', $this->validCalculatePayload(['width' => 500]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['width']);
    }

    public function test_calculate_validates_height_range(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->postJson('/api/calculate', $this->validCalculatePayload(['height' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['height']);
    }

    public function test_calculate_validates_invalid_product(): void
    {
        $this->createFullSupplierSetup();

        $this->postJson('/api/calculate', $this->validCalculatePayload(['product_id' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_calculate_validates_quantity_bounds(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->postJson('/api/calculate', $this->validCalculatePayload(['quantity' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        $this->postJson('/api/calculate', $this->validCalculatePayload(['quantity' => 101]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_calculate_returns_failure_for_missing_grid(): void
    {
        $this->createFullSupplierSetup();
        // No grid created

        $this->postJson('/api/calculate', $this->validCalculatePayload())
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }
}
