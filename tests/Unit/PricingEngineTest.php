<?php

namespace Tests\Unit;

use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption, PricingGrid, RoundingRule, Surcharge, CompatibilityRule, Quote, QuoteLineItem};
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private PricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PricingEngine();
    }

    // ── Basic Calculation ──────────────────────────────────────────

    public function test_basic_calculation_returns_success(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('pricing', $result);
        $this->assertArrayHasKey('breakdown', $result);
    }

    public function test_basic_calculation_returns_correct_grid_cost(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertEquals(100.00, $result['pricing']['grid_cost']);
    }

    public function test_markup_applied_correctly(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 50]));

        $this->assertEquals(100.00, $result['pricing']['unit_cost']);
        $this->assertEquals(150.00, $result['pricing']['unit_sell']);
    }

    public function test_zero_markup(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals($result['pricing']['unit_cost'], $result['pricing']['unit_sell']);
    }

    public function test_default_markup_from_supplier(): void
    {
        $this->createFullSupplierSetup(['supplier' => ['default_markup_pct' => 75.00]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $payload = $this->validCalculatePayload();
        unset($payload['markup_pct']);

        $result = $this->engine->calculate($payload);

        $this->assertEquals(75.0, $result['pricing']['markup_pct']);
        $this->assertEquals(175.00, $result['pricing']['unit_sell']);
    }

    public function test_quantity_multiplies_line_totals(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'quantity' => 3,
            'markup_pct' => 50,
        ]));

        $this->assertEquals(3, $result['pricing']['quantity']);
        $this->assertEquals(300.00, $result['pricing']['line_cost']);
        $this->assertEquals(450.00, $result['pricing']['line_sell']);
    }

    public function test_quantity_defaults_to_one(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $payload = $this->validCalculatePayload();
        unset($payload['quantity']);

        $result = $this->engine->calculate($payload);

        $this->assertEquals(1, $result['pricing']['quantity']);
    }

    public function test_margin_calculated_correctly(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 100]));

        $p = $result['pricing'];
        $this->assertEquals(100.00, $p['line_cost']);
        $this->assertEquals(200.00, $p['line_sell']);
        $this->assertEquals(100.00, $p['line_margin']);
        $this->assertEquals(50.0, $p['margin_pct']); // margin = 100/200 = 50%
    }

    // ── Dimension Validation ───────────────────────────────────────

    public function test_rejects_width_below_minimum(): void
    {
        $this->createFullSupplierSetup(['product' => ['min_width' => 12]]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 8]));

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['breakdown']['errors']);
        $this->assertStringContainsString('at least', $result['breakdown']['errors'][0]);
    }

    public function test_rejects_width_above_maximum(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144]]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 150]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceed', $result['breakdown']['errors'][0]);
    }

    public function test_rejects_height_below_minimum(): void
    {
        $this->createFullSupplierSetup(['product' => ['min_height' => 12]]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['height' => 6]));

        $this->assertFalse($result['success']);
    }

    public function test_rejects_height_above_maximum(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_height' => 120]]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['height' => 130]));

        $this->assertFalse($result['success']);
    }

    public function test_accepts_dimensions_at_exact_boundary(): void
    {
        $this->createFullSupplierSetup([
            'product' => ['min_width' => 12, 'max_width' => 48, 'min_height' => 12, 'max_height' => 60],
        ]);
        $this->createPricingGrid();

        $resultMin = $this->engine->calculate($this->validCalculatePayload(['width' => 12, 'height' => 12]));
        $resultMax = $this->engine->calculate($this->validCalculatePayload(['width' => 48, 'height' => 60]));

        $this->assertTrue($resultMin['success']);
        $this->assertTrue($resultMax['success']);
    }

    // ── Rounding ───────────────────────────────────────────────────

    public function test_rounds_up_to_half_inch(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'up', 'increment' => 0.5]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.3, 'height' => 48.1]));

        $this->assertTrue($result['success']);
        $this->assertEquals(36.5, $result['pricing']['rounded_width']);
        $this->assertEquals(48.5, $result['pricing']['rounded_height']);
    }

    public function test_rounds_down(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'down', 'increment' => 1.0]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.7, 'height' => 48.9]));

        $this->assertTrue($result['success']);
        $this->assertEquals(36.0, $result['pricing']['rounded_width']);
        $this->assertEquals(48.0, $result['pricing']['rounded_height']);
    }

    public function test_rounds_to_nearest(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'nearest', 'increment' => 1.0]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.3, 'height' => 48.7]));

        $this->assertTrue($result['success']);
        $this->assertEquals(36.0, $result['pricing']['rounded_width']);
        $this->assertEquals(49.0, $result['pricing']['rounded_height']);
    }

    public function test_rounds_to_next_whole(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'next_whole', 'increment' => 1.0]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.1, 'height' => 48.0]));

        $this->assertTrue($result['success']);
        $this->assertEquals(37.0, $result['pricing']['rounded_width']);
        $this->assertEquals(48.0, $result['pricing']['rounded_height']);
    }

    public function test_rounds_to_next_half(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'next_half', 'increment' => 0.5]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.1, 'height' => 48.0]));

        $this->assertTrue($result['success']);
        $this->assertEquals(36.5, $result['pricing']['rounded_width']);
        $this->assertEquals(48.0, $result['pricing']['rounded_height']);
    }

    public function test_product_specific_rounding_overrides_supplier_default(): void
    {
        $this->createFullSupplierSetup(['supplier' => ['rounding_method' => 'up', 'rounding_increment' => 1.0]]);
        $this->createRoundingRule([
            'product_id' => null,
            'method' => 'up',
            'increment' => 1.0,
        ]);
        $productRule = $this->createRoundingRule([
            'product_id' => $this->product->id,
            'method' => 'down',
            'increment' => 0.5,
        ]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.3]));

        $this->assertTrue($result['success']);
        $this->assertEquals(36.0, $result['pricing']['rounded_width']); // down to 0.5 → 36.0
    }

    // ── Grid Lookup ────────────────────────────────────────────────

    public function test_fails_when_no_grid_found(): void
    {
        $this->createFullSupplierSetup();
        // No grid created

        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No pricing found', $result['breakdown']['errors'][0]);
    }

    public function test_selects_correct_price_group(): void
    {
        $this->createFullSupplierSetup();
        $this->createWideGrid();

        // Fabric with price group B
        $fabricB = Fabric::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Premium Fabric',
            'code' => 'PRM',
            'collection' => 'Premium',
            'opacity' => 'blackout',
            'color' => 'Black',
            'price_group' => 'B',
            'price_modifier' => 0,
            'active' => true,
        ]);

        $resultA = $this->engine->calculate($this->validCalculatePayload());
        $resultB = $this->engine->calculate($this->validCalculatePayload(['fabric_id' => $fabricB->id]));

        $this->assertEquals(85.00, $resultA['pricing']['grid_cost']);
        $this->assertEquals(105.00, $resultB['pricing']['grid_cost']);
    }

    public function test_fails_when_dimensions_fall_outside_all_grids(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_height' => 200]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['height' => 80]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No pricing found', $result['breakdown']['errors'][0]);
    }

    // ── Fabric Modifier ────────────────────────────────────────────

    public function test_fabric_price_modifier_added(): void
    {
        $this->createFullSupplierSetup(['fabric' => ['price_modifier' => 10.00]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // grid 100 + fabric modifier 10 = 110 unit cost (no control adder, no options, no surcharges)
        $this->assertEquals(110.00, $result['pricing']['unit_cost']);
    }

    public function test_negative_fabric_modifier(): void
    {
        $this->createFullSupplierSetup(['fabric' => ['price_modifier' => -5.00]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals(95.00, $result['pricing']['unit_cost']);
    }

    // ── Control Type Pricing ───────────────────────────────────────

    public function test_control_type_adder(): void
    {
        $this->createFullSupplierSetup(['control_type' => ['price_adder' => 25.00, 'price_multiplier' => 1.000]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // grid 100 + fabric 0 = 100, then (100 * 1.0) + 25 = 125
        $this->assertEquals(125.00, $result['pricing']['unit_cost']);
    }

    public function test_control_type_multiplier(): void
    {
        $this->createFullSupplierSetup(['control_type' => ['price_adder' => 0, 'price_multiplier' => 1.500]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // (100 * 1.5) + 0 = 150
        $this->assertEquals(150.00, $result['pricing']['unit_cost']);
    }

    public function test_control_type_adder_and_multiplier_combined(): void
    {
        $this->createFullSupplierSetup(['control_type' => ['price_adder' => 20.00, 'price_multiplier' => 1.200]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // (100 * 1.2) + 20 = 140
        $this->assertEquals(140.00, $result['pricing']['unit_cost']);
    }

    // ── Options ────────────────────────────────────────────────────

    public function test_option_flat_adder(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $option = $this->createOption(['price_adder' => 15.00, 'price_pct' => 0]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$option->id],
            'markup_pct' => 0,
        ]));

        $this->assertEquals(15.00, $result['pricing']['options_adder']);
        $this->assertEquals(115.00, $result['pricing']['unit_cost']);
    }

    public function test_option_percentage(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $option = $this->createOption(['price_adder' => 0, 'price_pct' => 10, 'code' => 'PCT']);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$option->id],
            'markup_pct' => 0,
        ]));

        // 10% of afterControl (100) = 10
        $this->assertEquals(10.00, $result['pricing']['options_adder']);
    }

    public function test_multiple_options_stack(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $opt1 = $this->createOption(['price_adder' => 15.00, 'price_pct' => 0, 'code' => 'OPT1', 'name' => 'Option 1']);
        $opt2 = $this->createOption(['price_adder' => 10.00, 'price_pct' => 5, 'code' => 'OPT2', 'name' => 'Option 2']);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$opt1->id, $opt2->id],
            'markup_pct' => 0,
        ]));

        // opt1: 15 + 0 = 15, opt2: 10 + (100 * 5/100) = 15, total = 30
        $this->assertEquals(30.00, $result['pricing']['options_adder']);
        $this->assertEquals(130.00, $result['pricing']['unit_cost']);
    }

    public function test_no_options_means_zero_adder(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals(0.00, $result['pricing']['options_adder']);
    }

    // ── Surcharges ─────────────────────────────────────────────────

    public function test_oversize_width_flat_surcharge(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 120,
            'dealer_cost' => 200.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'width',
            'trigger_value' => 96,
            'charge_type' => 'flat',
            'charge_amount' => 25.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 100, 'height' => 48, 'markup_pct' => 0,
        ]));

        $this->assertEquals(25.00, $result['pricing']['surcharges']);
        $this->assertEquals(225.00, $result['pricing']['unit_cost']);
    }

    public function test_oversize_not_triggered_when_under_threshold(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'width',
            'trigger_value' => 96,
            'charge_type' => 'flat',
            'charge_amount' => 25.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 36, 'markup_pct' => 0,
        ]));

        $this->assertEquals(0.00, $result['pricing']['surcharges']);
    }

    public function test_percentage_surcharge(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_height' => 200]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 200,
            'dealer_cost' => 200.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'height',
            'trigger_value' => 100,
            'charge_type' => 'percentage',
            'charge_amount' => 15,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 36, 'height' => 110, 'markup_pct' => 0,
        ]));

        // 15% of afterOptions (200) = 30
        $this->assertEquals(30.00, $result['pricing']['surcharges']);
    }

    public function test_per_sqft_surcharge(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144, 'max_height' => 120]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 120,
            'dealer_cost' => 100.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'sqft',
            'trigger_value' => 20,
            'charge_type' => 'per_sqft',
            'charge_amount' => 2.50,
        ]);

        // 72 x 48 = 3456 sq in = 24 sqft > 20 threshold
        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 72, 'height' => 48, 'markup_pct' => 0,
        ]));

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['pricing']['surcharges']);
    }

    public function test_united_inches_surcharge(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144, 'max_height' => 120]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 120,
            'dealer_cost' => 100.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'united_inches',
            'trigger_value' => 150,
            'charge_type' => 'per_united_inch',
            'charge_amount' => 0.50,
        ]);

        // 100 + 80 = 180 united inches > 150
        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 100, 'height' => 80, 'markup_pct' => 0,
        ]));

        // 180 * 0.50 = 90
        $this->assertEquals(90.00, $result['pricing']['surcharges']);
    }

    public function test_undersize_surcharge(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();
        $this->createSurcharge([
            'trigger_type' => 'undersize',
            'trigger_dimension' => 'width',
            'trigger_value' => 24,
            'charge_type' => 'flat',
            'charge_amount' => 15.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 18, 'markup_pct' => 0,
        ]));

        $this->assertEquals(15.00, $result['pricing']['surcharges']);
    }

    public function test_rush_surcharge_always_applies(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $this->createSurcharge([
            'trigger_type' => 'rush',
            'trigger_value' => null,
            'trigger_dimension' => null,
            'charge_type' => 'flat',
            'charge_amount' => 50.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals(50.00, $result['pricing']['surcharges']);
    }

    public function test_multiple_surcharges_stack(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 120,
            'dealer_cost' => 100.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'width',
            'trigger_value' => 96,
            'charge_type' => 'flat',
            'charge_amount' => 25.00,
        ]);
        $this->createSurcharge([
            'trigger_type' => 'rush',
            'trigger_value' => null,
            'trigger_dimension' => null,
            'charge_type' => 'flat',
            'charge_amount' => 50.00,
            'name' => 'Rush Fee',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 100, 'height' => 48, 'markup_pct' => 0,
        ]));

        $this->assertEquals(75.00, $result['pricing']['surcharges']); // 25 + 50
    }

    // ── Compatibility Rules ────────────────────────────────────────

    public function test_excludes_rule_blocks_incompatible_fabric_control(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $motorized = ControlType::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Motorized',
            'code' => 'MOT',
            'price_adder' => 100,
            'price_multiplier' => 1.0,
            'active' => true,
        ]);

        $this->createCompatibilityRule([
            'rule_type' => 'excludes',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'control_type',
            'target_id' => $motorized->id,
            'message' => 'Solar Screen 3% cannot be motorized.',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'control_type_id' => $motorized->id,
        ]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be motorized', $result['breakdown']['errors'][0]);
    }

    public function test_excludes_rule_allows_compatible_combination(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $motorized = ControlType::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Motorized',
            'code' => 'MOT',
            'price_adder' => 100,
            'price_multiplier' => 1.0,
            'active' => true,
        ]);

        // Rule blocks fabric + motorized, but we're using standard chain
        $this->createCompatibilityRule([
            'rule_type' => 'excludes',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'control_type',
            'target_id' => $motorized->id,
            'message' => 'Incompatible.',
        ]);

        // Using standard chain (not motorized) — should pass
        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertTrue($result['success']);
    }

    public function test_requires_rule_blocks_when_missing(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $requiredOption = $this->createOption(['code' => 'REQ', 'name' => 'Required Option']);

        $this->createCompatibilityRule([
            'rule_type' => 'requires',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'option',
            'target_id' => $requiredOption->id,
            'message' => 'This fabric requires the Required Option.',
        ]);

        // Not including the required option
        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('requires', $result['breakdown']['errors'][0]);
    }

    public function test_requires_rule_passes_when_present(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $requiredOption = $this->createOption(['code' => 'REQ', 'name' => 'Required Option']);

        $this->createCompatibilityRule([
            'rule_type' => 'requires',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'option',
            'target_id' => $requiredOption->id,
            'message' => 'This fabric requires the Required Option.',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$requiredOption->id],
        ]));

        $this->assertTrue($result['success']);
    }

    public function test_inactive_compatibility_rule_ignored(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->createCompatibilityRule([
            'rule_type' => 'excludes',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'control_type',
            'target_id' => $this->controlType->id,
            'message' => 'Should not fire.',
            'active' => false,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload());

        $this->assertTrue($result['success']);
    }

    // ── Breakdown ──────────────────────────────────────────────────

    public function test_breakdown_contains_all_steps(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload());

        $steps = collect($result['breakdown']['steps'])->pluck('step')->toArray();
        $this->assertContains('rounding', $steps);
        $this->assertContains('grid_lookup', $steps);
        $this->assertContains('fabric_modifier', $steps);
        $this->assertContains('control_type', $steps);
        $this->assertContains('options', $steps);
        $this->assertContains('surcharges', $steps);
        $this->assertContains('markup', $steps);
    }

    public function test_breakdown_input_recorded(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36, 'height' => 48]));

        $this->assertEquals(36, $result['breakdown']['input']['width']);
        $this->assertEquals(48, $result['breakdown']['input']['height']);
    }

    // ── Full Integration ───────────────────────────────────────────

    public function test_full_calculation_with_all_modifiers(): void
    {
        $this->createFullSupplierSetup([
            'fabric' => ['price_modifier' => 5.00],
            'control_type' => ['price_adder' => 20.00, 'price_multiplier' => 1.100],
        ]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $option = $this->createOption(['price_adder' => 10.00, 'price_pct' => 5, 'code' => 'FULL']);
        $this->createSurcharge([
            'trigger_type' => 'rush',
            'trigger_value' => null,
            'trigger_dimension' => null,
            'charge_type' => 'flat',
            'charge_amount' => 30.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$option->id],
            'markup_pct' => 50,
            'quantity' => 2,
        ]));

        $this->assertTrue($result['success']);

        $p = $result['pricing'];
        // grid=100, +fabric=5 → 105, *1.1+20 = 135.50
        // option: 10 + (135.50 * 5/100) = 10 + 6.775 = 16.78 (rounded)
        // afterOptions: 135.50 + 16.775 = 152.275
        // rush: +30 → unit_cost = 182.275
        // sell: 182.275 * 1.5 = 273.41 (rounded)
        // line: *2

        $this->assertEquals(2, $p['quantity']);
        $this->assertGreaterThan(0, $p['unit_cost']);
        $this->assertGreaterThan($p['unit_cost'], $p['unit_sell']);
        $this->assertEqualsWithDelta($p['unit_cost'] * 2, $p['line_cost'], 0.02);
        $this->assertEqualsWithDelta($p['unit_sell'] * 2, $p['line_sell'], 0.02);
    }

    // ── Grid Boundary Edge Cases ───────────────────────────────────

    public function test_grid_boundary_collision_returns_deterministic_result(): void
    {
        $this->createFullSupplierSetup();

        // Two grids share the boundary at width=48
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
            'dealer_cost' => 85.00,
        ]);
        $this->createPricingGrid([
            'width_min' => 48, 'width_max' => 96,
            'height_min' => 12, 'height_max' => 60,
            'dealer_cost' => 135.00,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 48]));

        // Must succeed and return one of the two costs — never fail
        $this->assertTrue($result['success']);
        $this->assertContains($result['pricing']['grid_cost'], [85.00, 135.00]);
    }

    public function test_grid_gap_between_ranges_returns_failure(): void
    {
        $this->createFullSupplierSetup();

        // Gap: Grid A ends at 47, Grid B starts at 49
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 47,
            'height_min' => 12, 'height_max' => 60,
            'dealer_cost' => 85.00,
        ]);
        $this->createPricingGrid([
            'width_min' => 49, 'width_max' => 96,
            'height_min' => 12, 'height_max' => 60,
            'dealer_cost' => 135.00,
        ]);

        // Width 48 falls in the gap
        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 48]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No pricing found', $result['breakdown']['errors'][0]);
    }

    public function test_grid_exact_min_boundary(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid([
            'width_min' => 24, 'width_max' => 48,
            'height_min' => 24, 'height_max' => 60,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 24, 'height' => 24]));
        $this->assertTrue($result['success']);
    }

    public function test_grid_exact_max_boundary(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 48, 'height' => 60]));
        $this->assertTrue($result['success']);
    }

    // ── Rounding Edge Cases ────────────────────────────────────────

    public function test_rounding_with_zero_increment_passes_through(): void
    {
        $this->createFullSupplierSetup(['supplier' => ['rounding_increment' => 0]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.7, 'height' => 48.3]));

        $this->assertTrue($result['success']);
        // With zero increment, values pass through unrounded
        $this->assertEquals(36.7, $result['pricing']['rounded_width']);
        $this->assertEquals(48.3, $result['pricing']['rounded_height']);
    }

    public function test_rounding_with_whole_numbers_stays_exact(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['method' => 'up', 'increment' => 0.5]);
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.0, 'height' => 48.0]));

        $this->assertEquals(36.0, $result['pricing']['rounded_width']);
        $this->assertEquals(48.0, $result['pricing']['rounded_height']);
    }

    public function test_rounding_falls_back_to_supplier_default_without_rule(): void
    {
        $this->createFullSupplierSetup([
            'supplier' => ['rounding_method' => 'nearest', 'rounding_increment' => 1.0],
        ]);
        // No RoundingRule created — should use supplier defaults
        $this->createPricingGrid();

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 36.7, 'height' => 48.3]));

        $this->assertTrue($result['success']);
        $this->assertEquals(37.0, $result['pricing']['rounded_width']);
        $this->assertEquals(48.0, $result['pricing']['rounded_height']);
    }

    // ── Negative/Zero Value Safety ─────────────────────────────────

    public function test_large_negative_fabric_modifier_can_reduce_cost(): void
    {
        $this->createFullSupplierSetup(['fabric' => ['price_modifier' => -50.00]]);
        $this->createPricingGrid(['dealer_cost' => 80.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // 80 + (-50) = 30
        $this->assertTrue($result['success']);
        $this->assertEquals(30.00, $result['pricing']['unit_cost']);
    }

    public function test_zero_control_multiplier(): void
    {
        $this->createFullSupplierSetup(['control_type' => [
            'price_adder' => 50.00,
            'price_multiplier' => 0,
        ]]);
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        // (100 * 0) + 50 = 50
        $this->assertTrue($result['success']);
        $this->assertEquals(50.00, $result['pricing']['unit_cost']);
    }

    public function test_zero_grid_cost(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 0]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 50]));

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['pricing']['grid_cost']);
        $this->assertEquals(0, $result['pricing']['unit_sell']);
    }

    public function test_very_large_markup(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 500]));

        $this->assertTrue($result['success']);
        $this->assertEquals(600.00, $result['pricing']['unit_sell']); // 100 * 6
        $this->assertGreaterThan(80, $result['pricing']['margin_pct']); // ~83.3%
    }

    public function test_option_percentage_on_zero_base(): void
    {
        $this->createFullSupplierSetup(['control_type' => [
            'price_adder' => 0,
            'price_multiplier' => 0,
        ]]);
        $this->createPricingGrid(['dealer_cost' => 0]);
        $option = $this->createOption(['price_adder' => 0, 'price_pct' => 50]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$option->id],
            'markup_pct' => 0,
        ]));

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['pricing']['unit_cost']);
    }

    public function test_margin_pct_zero_when_sell_is_zero(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 0]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['pricing']['margin_pct']); // Guarded: $lineSell > 0 check
    }

    // ── Surcharge Stacking & Edge Cases ────────────────────────────

    public function test_multiple_percentage_surcharges_are_additive_not_compound(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 200, 'max_height' => 200]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 200,
            'height_min' => 12, 'height_max' => 200,
            'dealer_cost' => 100.00,
        ]);

        // Both percentage surcharges on same trigger
        $this->createSurcharge([
            'name' => 'Oversize 10%',
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'width',
            'trigger_value' => 50,
            'charge_type' => 'percentage',
            'charge_amount' => 10,
        ]);
        $this->createSurcharge([
            'name' => 'Oversize 15%',
            'trigger_type' => 'oversize',
            'trigger_dimension' => 'width',
            'trigger_value' => 50,
            'charge_type' => 'percentage',
            'charge_amount' => 15,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 60, 'height' => 48, 'markup_pct' => 0,
        ]));

        // Both calculated off same basePrice (100), additive: 10 + 15 = 25
        $this->assertTrue($result['success']);
        $this->assertEquals(25.00, $result['pricing']['surcharges']);
    }

    public function test_surcharge_on_tiny_dimensions(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 20.00]);
        $this->createSurcharge([
            'trigger_type' => 'undersize',
            'trigger_dimension' => 'sqft',
            'trigger_value' => 5,
            'charge_type' => 'per_sqft',
            'charge_amount' => 50.00,
        ]);

        // 12x12 = 1 sqft, under 5 sqft threshold
        $result = $this->engine->calculate($this->validCalculatePayload([
            'width' => 12, 'height' => 12, 'markup_pct' => 0,
        ]));

        $this->assertTrue($result['success']);
        // Per-sqft surcharge on 1 sqft = $50, which is 250% of grid cost
        $this->assertEquals(50.00, $result['pricing']['surcharges']);
        $this->assertEquals(70.00, $result['pricing']['unit_cost']);
    }

    public function test_supplier_wide_surcharge_applies_to_all_products(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        // Supplier-wide surcharge (product_id = null)
        Surcharge::create([
            'supplier_id' => $this->supplier->id,
            'product_id' => null,
            'name' => 'Rush Fee',
            'trigger_type' => 'rush',
            'trigger_value' => null,
            'trigger_dimension' => null,
            'charge_type' => 'flat',
            'charge_amount' => 40.00,
            'active' => true,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals(40.00, $result['pricing']['surcharges']);
    }

    public function test_inactive_surcharge_not_applied(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $this->createSurcharge([
            'trigger_type' => 'rush',
            'trigger_value' => null,
            'trigger_dimension' => null,
            'charge_type' => 'flat',
            'charge_amount' => 50.00,
            'active' => false,
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['markup_pct' => 0]));

        $this->assertEquals(0, $result['pricing']['surcharges']);
        $this->assertEquals(100.00, $result['pricing']['unit_cost']);
    }

    // ── Compatibility Rule Edge Cases ──────────────────────────────

    public function test_max_size_rule_at_exact_threshold_passes(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->createCompatibilityRule([
            'rule_type' => 'max_size_with',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'dimension',
            'target_id' => null,
            'target_value' => 48,
            'message' => 'This fabric cannot exceed 48 inches.',
        ]);

        // Exactly at threshold — should pass (rule checks > not >=)
        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 48, 'height' => 48]));
        $this->assertTrue($result['success']);
    }

    public function test_max_size_rule_over_threshold_fails(): void
    {
        $this->createFullSupplierSetup(['product' => ['max_width' => 144]]);
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 60,
        ]);

        $this->createCompatibilityRule([
            'rule_type' => 'max_size_with',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'dimension',
            'target_id' => null,
            'target_value' => 48,
            'message' => 'Fabric max size 48 inches.',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 50, 'height' => 48]));
        $this->assertFalse($result['success']);
    }

    public function test_min_size_rule_at_exact_threshold_passes(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->createCompatibilityRule([
            'rule_type' => 'min_size_with',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'dimension',
            'target_id' => null,
            'target_value' => 24,
            'message' => 'Minimum 24 inches required.',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 24, 'height' => 24]));
        $this->assertTrue($result['success']);
    }

    public function test_min_size_rule_under_threshold_fails(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->createCompatibilityRule([
            'rule_type' => 'min_size_with',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'dimension',
            'target_id' => null,
            'target_value' => 24,
            'message' => 'Min 24 inches.',
        ]);

        $result = $this->engine->calculate($this->validCalculatePayload(['width' => 20, 'height' => 48]));
        $this->assertFalse($result['success']);
    }

    public function test_option_based_compatibility_rule(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $optA = $this->createOption(['code' => 'OPT_A', 'name' => 'Option A']);
        $optB = $this->createOption(['code' => 'OPT_B', 'name' => 'Option B']);

        // Option A excludes Option B
        $this->createCompatibilityRule([
            'rule_type' => 'excludes',
            'subject_type' => 'option',
            'subject_id' => $optA->id,
            'target_type' => 'option',
            'target_id' => $optB->id,
            'message' => 'Option A and Option B cannot be combined.',
        ]);

        // Both selected → should fail
        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$optA->id, $optB->id],
        ]));
        $this->assertFalse($result['success']);

        // Only A → should pass
        $result = $this->engine->calculate($this->validCalculatePayload([
            'option_ids' => [$optA->id],
        ]));
        $this->assertTrue($result['success']);
    }

    // ── Cross-Supplier Isolation ───────────────────────────────────

    public function test_cross_supplier_products_not_mixed(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $supplierB = Supplier::create([
            'name' => 'Supplier B', 'code' => 'SUP_B', 'active' => true,
            'rounding_method' => 'up', 'rounding_increment' => 1,
            'default_markup_pct' => 40,
        ]);
        $productB = Product::create([
            'supplier_id' => $supplierB->id,
            'name' => 'Vertical Blind',
            'code' => 'VB-001',
            'category' => 'verticals',
            'min_width' => 12, 'max_width' => 144,
            'min_height' => 12, 'max_height' => 120,
            'active' => true,
        ]);

        // Fabric belongs to supplier A, product to supplier B
        // Grid lookup will fail because no grid exists for product B
        $result = $this->engine->calculate([
            'product_id' => $productB->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
        ]);

        // Should fail — no pricing grid for product B
        $this->assertFalse($result['success']);
    }

    public function test_multi_supplier_quote_totals(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);

        $supplierB = Supplier::create([
            'name' => 'Supplier B', 'code' => 'SUP_B', 'active' => true,
            'rounding_method' => 'up', 'rounding_increment' => 1,
            'default_markup_pct' => 40,
        ]);
        $productB = Product::create([
            'supplier_id' => $supplierB->id,
            'name' => 'Wood Blind', 'code' => 'WB-001',
            'category' => 'wood_blinds',
            'min_width' => 12, 'max_width' => 72,
            'min_height' => 12, 'max_height' => 96,
            'active' => true,
        ]);
        $fabricB = Fabric::create([
            'supplier_id' => $supplierB->id,
            'name' => 'Basswood', 'code' => 'BW',
            'opacity' => 'room_darkening', 'color' => 'Natural',
            'price_group' => 'A', 'price_modifier' => 0, 'active' => true,
        ]);
        $controlB = ControlType::create([
            'supplier_id' => $supplierB->id,
            'name' => 'Wand', 'code' => 'WND',
            'price_adder' => 0, 'price_multiplier' => 1.0, 'active' => true,
        ]);
        PricingGrid::create([
            'product_id' => $productB->id,
            'width_min' => 12, 'width_max' => 72,
            'height_min' => 12, 'height_max' => 96,
            'price_group' => 'A', 'dealer_cost' => 150.00,
        ]);

        $quote = $this->createQuote();

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'grid_cost' => 100, 'unit_cost' => 100, 'markup_pct' => 50,
            'unit_sell' => 150, 'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
        ]);
        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $productB->id,
            'fabric_id' => $fabricB->id,
            'control_type_id' => $controlB->id,
            'width' => 48, 'height' => 60,
            'rounded_width' => 48, 'rounded_height' => 60,
            'quantity' => 1, 'mount_type' => 'outside',
            'grid_cost' => 150, 'unit_cost' => 150, 'markup_pct' => 40,
            'unit_sell' => 210, 'line_cost' => 150, 'line_sell' => 210, 'line_margin' => 60,
        ]);

        $quote->recalculateTotals();

        $this->assertEquals(250.00, $quote->total_cost);   // 100 + 150
        $this->assertEquals(360.00, $quote->total_sell);    // 150 + 210
        $this->assertEquals(110.00, $quote->total_margin);  // 360 - 250
    }
}
