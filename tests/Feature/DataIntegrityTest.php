<?php

namespace Tests\Feature;

use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption, PricingGrid, Quote, QuoteLineItem};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ── Cascading Deletes ──────────────────────────────────────────

    public function test_deleting_supplier_cascades_to_products(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();

        $this->supplier->delete();

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('fabrics', 0);
        $this->assertDatabaseCount('control_types', 0);
        $this->assertDatabaseCount('pricing_grids', 0);
    }

    public function test_deleting_product_cascades_to_grids(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid();
        $this->createPricingGrid([
            'width_min' => 49, 'width_max' => 96,
            'dealer_cost' => 135.00,
        ]);

        $this->assertDatabaseCount('pricing_grids', 2);
        $this->product->delete();
        $this->assertDatabaseCount('pricing_grids', 0);
    }

    public function test_deleting_product_cascades_to_rounding_rules(): void
    {
        $this->createFullSupplierSetup();
        $this->createRoundingRule(['product_id' => $this->product->id]);

        $this->product->delete();
        $this->assertDatabaseCount('rounding_rules', 0);
    }

    public function test_deleting_product_cascades_to_surcharges(): void
    {
        $this->createFullSupplierSetup();
        $this->createSurcharge();

        $this->product->delete();
        $this->assertDatabaseCount('surcharges', 0);
    }

    public function test_deleting_product_cascades_to_compatibility_rules(): void
    {
        $this->createFullSupplierSetup();
        $this->createCompatibilityRule();

        $this->product->delete();
        $this->assertDatabaseCount('compatibility_rules', 0);
    }

    // ── Quote Isolation from Catalog Changes ───────────────────────

    public function test_quote_line_preserves_pricing_after_grid_change(): void
    {
        $this->createFullSupplierSetup();
        $grid = $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'grid_cost' => 100, 'unit_cost' => 100,
            'markup_pct' => 50, 'unit_sell' => 150,
            'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
        ]);

        // Admin changes grid price
        $grid->update(['dealer_cost' => 200.00]);

        // Existing line item still has original cost
        $line = QuoteLineItem::first();
        $this->assertEquals(100.00, $line->grid_cost);
        $this->assertEquals(100.00, $line->line_cost);
    }

    public function test_quote_totals_survive_product_deactivation(): void
    {
        $this->createFullSupplierSetup();
        $this->createPricingGrid(['dealer_cost' => 100.00]);
        $quote = $this->createQuote();

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'grid_cost' => 100, 'unit_cost' => 100,
            'markup_pct' => 50, 'unit_sell' => 150,
            'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
        ]);

        $quote->recalculateTotals();
        $this->assertEquals(100.00, $quote->total_cost);

        // Product deactivated
        $this->product->update(['active' => false]);

        // Quote totals unchanged — line items are historical records
        $quote->refresh();
        $this->assertEquals(100.00, $quote->total_cost);
        $this->assertEquals(150.00, $quote->total_sell);
    }

    // ── Unique Constraints ─────────────────────────────────────────

    public function test_supplier_code_must_be_unique(): void
    {
        $this->createFullSupplierSetup();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Supplier::create([
            'name' => 'Duplicate',
            'code' => 'TEST', // Same as existing
            'active' => true,
        ]);
    }

    public function test_product_code_unique_per_supplier(): void
    {
        $this->createFullSupplierSetup();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Product::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Duplicate',
            'code' => 'RS-001', // Same as existing product for this supplier
            'category' => 'roller_shades',
        ]);
    }

    public function test_same_product_code_allowed_for_different_suppliers(): void
    {
        $this->createFullSupplierSetup();

        $supplierB = Supplier::create([
            'name' => 'Other', 'code' => 'OTH', 'active' => true,
            'rounding_method' => 'up', 'rounding_increment' => 1,
            'default_markup_pct' => 40,
        ]);

        $product = Product::create([
            'supplier_id' => $supplierB->id,
            'name' => 'Same Code Different Supplier',
            'code' => 'RS-001', // Same code, different supplier — should work
            'category' => 'roller_shades',
        ]);

        $this->assertNotNull($product->id);
    }

    public function test_fabric_code_unique_per_supplier(): void
    {
        $this->createFullSupplierSetup();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Fabric::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Duplicate Fabric',
            'code' => 'SS3', // Same as existing
            'opacity' => 'blackout',
            'color' => 'Black',
        ]);
    }

    public function test_control_type_code_unique_per_supplier(): void
    {
        $this->createFullSupplierSetup();

        $this->expectException(\Illuminate\Database\QueryException::class);

        ControlType::create([
            'supplier_id' => $this->supplier->id,
            'name' => 'Duplicate Control',
            'code' => 'STD', // Same as existing
        ]);
    }

    // ── QuoteLineItem JSON Casting ─────────────────────────────────

    public function test_selected_options_stored_and_retrieved_as_array(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();
        $opt = $this->createOption();

        $line = QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'selected_options' => [$opt->id],
            'grid_cost' => 100, 'unit_cost' => 115,
            'markup_pct' => 50, 'unit_sell' => 172.50,
            'line_cost' => 115, 'line_sell' => 172.50, 'line_margin' => 57.50,
            'pricing_breakdown' => ['steps' => [['step' => 'grid_lookup', 'dealer_cost' => 100]]],
        ]);

        $fresh = QuoteLineItem::find($line->id);
        $this->assertIsArray($fresh->selected_options);
        $this->assertEquals([$opt->id], $fresh->selected_options);
        $this->assertIsArray($fresh->pricing_breakdown);
        $this->assertEquals('grid_lookup', $fresh->pricing_breakdown['steps'][0]['step']);
    }

    public function test_null_pricing_breakdown_returns_null(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $line = QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'selected_options' => null,
            'grid_cost' => 100, 'unit_cost' => 100,
            'markup_pct' => 50, 'unit_sell' => 150,
            'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
            'pricing_breakdown' => null,
        ]);

        $fresh = QuoteLineItem::find($line->id);
        $this->assertNull($fresh->pricing_breakdown);
        $this->assertNull($fresh->selected_options);
    }

    // ── Quote Number Edge Cases ────────────────────────────────────

    public function test_first_quote_of_month_starts_at_0001(): void
    {
        $number = Quote::generateNumber();
        $this->assertStringEndsWith('-0001', $number);
    }

    public function test_quote_number_survives_deletion(): void
    {
        Quote::create(['quote_number' => 'Q-' . date('Ym') . '-0005', 'status' => 'draft']);
        Quote::first()->delete();

        // After deleting, next number should be 0001 since no records exist
        $number = Quote::generateNumber();
        $this->assertStringEndsWith('-0001', $number);
    }

    public function test_quote_number_continues_after_high_sequence(): void
    {
        Quote::create(['quote_number' => 'Q-' . date('Ym') . '-0099', 'status' => 'draft']);

        $number = Quote::generateNumber();
        $this->assertStringEndsWith('-0100', $number);
    }

    // ── Relationship Integrity ─────────────────────────────────────

    public function test_line_item_relationships_load_correctly(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        $line = QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'grid_cost' => 100, 'unit_cost' => 100,
            'markup_pct' => 50, 'unit_sell' => 150,
            'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
        ]);

        $loaded = QuoteLineItem::with(['product.supplier', 'fabric', 'controlType', 'quote'])->find($line->id);

        $this->assertEquals('Roller Shade', $loaded->product->name);
        $this->assertEquals('Test Supplier', $loaded->product->supplier->name);
        $this->assertEquals('Solar Screen 3%', $loaded->fabric->name);
        $this->assertEquals('Standard Chain', $loaded->controlType->name);
        $this->assertEquals($quote->quote_number, $loaded->quote->quote_number);
    }

    public function test_supplier_has_many_relationships(): void
    {
        $this->createFullSupplierSetup();
        $this->createOption();
        $this->createRoundingRule();
        $this->createSurcharge();
        $this->createCompatibilityRule();

        $this->supplier->load('products', 'fabrics', 'controlTypes', 'productOptions', 'roundingRules', 'surcharges', 'compatibilityRules');

        $this->assertCount(1, $this->supplier->products);
        $this->assertCount(1, $this->supplier->fabrics);
        $this->assertCount(1, $this->supplier->controlTypes);
        $this->assertCount(1, $this->supplier->productOptions);
        $this->assertCount(1, $this->supplier->roundingRules);
        $this->assertCount(1, $this->supplier->surcharges);
        $this->assertCount(1, $this->supplier->compatibilityRules);
    }

    public function test_deleting_quote_cascades_to_line_items(): void
    {
        $this->createFullSupplierSetup();
        $quote = $this->createQuote();

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36, 'height' => 48,
            'rounded_width' => 36, 'rounded_height' => 48,
            'quantity' => 1, 'mount_type' => 'inside',
            'grid_cost' => 100, 'unit_cost' => 100,
            'markup_pct' => 50, 'unit_sell' => 150,
            'line_cost' => 100, 'line_sell' => 150, 'line_margin' => 50,
        ]);

        $this->assertDatabaseCount('quote_line_items', 1);
        $quote->delete();
        $this->assertDatabaseCount('quote_line_items', 0);
    }
}
