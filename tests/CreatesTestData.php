<?php

namespace Tests;

use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption, PricingGrid, RoundingRule, Surcharge, CompatibilityRule, Quote, QuoteLineItem};

trait CreatesTestData
{
    protected Supplier $supplier;
    protected Product $product;
    protected Fabric $fabric;
    protected ControlType $controlType;

    protected function createFullSupplierSetup(array $overrides = []): void
    {
        $this->supplier = Supplier::create(array_merge([
            'name' => 'Test Supplier',
            'code' => 'TEST',
            'rounding_method' => 'up',
            'rounding_increment' => 0.5,
            'default_markup_pct' => 50.00,
            'active' => true,
        ], $overrides['supplier'] ?? []));

        $this->product = Product::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'name' => 'Roller Shade',
            'code' => 'RS-001',
            'category' => 'roller_shades',
            'min_width' => 12,
            'max_width' => 144,
            'min_height' => 12,
            'max_height' => 120,
            'base_price' => 0,
            'lead_time_days' => 10,
            'active' => true,
        ], $overrides['product'] ?? []));

        $this->fabric = Fabric::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'name' => 'Solar Screen 3%',
            'code' => 'SS3',
            'collection' => 'Solar',
            'opacity' => 'light_filtering',
            'color' => 'White',
            'color_hex' => '#FFFFFF',
            'price_modifier' => 0,
            'price_group' => 'A',
            'active' => true,
        ], $overrides['fabric'] ?? []));

        $this->controlType = ControlType::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'name' => 'Standard Chain',
            'code' => 'STD',
            'price_adder' => 0,
            'price_multiplier' => 1.000,
            'active' => true,
        ], $overrides['control_type'] ?? []));
    }

    protected function createPricingGrid(array $overrides = []): PricingGrid
    {
        return PricingGrid::create(array_merge([
            'product_id' => $this->product->id,
            'width_min' => 12,
            'width_max' => 48,
            'height_min' => 12,
            'height_max' => 60,
            'price_group' => 'A',
            'dealer_cost' => 85.00,
        ], $overrides));
    }

    protected function createWideGrid(): void
    {
        // Small
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
            'price_group' => 'A', 'dealer_cost' => 85.00,
        ]);
        // Medium
        $this->createPricingGrid([
            'width_min' => 48.01, 'width_max' => 96,
            'height_min' => 12, 'height_max' => 60,
            'price_group' => 'A', 'dealer_cost' => 135.00,
        ]);
        // Large
        $this->createPricingGrid([
            'width_min' => 96.01, 'width_max' => 144,
            'height_min' => 12, 'height_max' => 120,
            'price_group' => 'A', 'dealer_cost' => 225.00,
        ]);
        // Price group B
        $this->createPricingGrid([
            'width_min' => 12, 'width_max' => 48,
            'height_min' => 12, 'height_max' => 60,
            'price_group' => 'B', 'dealer_cost' => 105.00,
        ]);
    }

    protected function createOption(array $overrides = []): ProductOption
    {
        return ProductOption::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'name' => 'Cordless Upgrade',
            'code' => 'CORD',
            'group' => 'upgrade',
            'price_adder' => 15.00,
            'price_pct' => 0,
            'active' => true,
        ], $overrides));
    }

    protected function createSurcharge(array $overrides = []): Surcharge
    {
        return Surcharge::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'product_id' => $this->product->id,
            'name' => 'Oversize Width Surcharge',
            'trigger_type' => 'oversize',
            'trigger_value' => 96,
            'trigger_dimension' => 'width',
            'charge_type' => 'flat',
            'charge_amount' => 25.00,
            'active' => true,
        ], $overrides));
    }

    protected function createRoundingRule(array $overrides = []): RoundingRule
    {
        return RoundingRule::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'product_id' => null,
            'dimension' => 'both',
            'method' => 'up',
            'increment' => 0.5,
        ], $overrides));
    }

    protected function createCompatibilityRule(array $overrides = []): CompatibilityRule
    {
        return CompatibilityRule::create(array_merge([
            'supplier_id' => $this->supplier->id,
            'product_id' => $this->product->id,
            'rule_type' => 'excludes',
            'subject_type' => 'fabric',
            'subject_id' => $this->fabric->id,
            'target_type' => 'control_type',
            'target_id' => $this->controlType->id,
            'message' => 'This fabric is not compatible with this control type.',
            'active' => true,
        ], $overrides));
    }

    protected function createQuote(array $overrides = []): Quote
    {
        return Quote::create(array_merge([
            'quote_number' => Quote::generateNumber(),
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '555-0100',
            'project_name' => 'Test Project',
            'status' => 'draft',
            'valid_until' => now()->addDays(30),
        ], $overrides));
    }

    protected function validCalculatePayload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product->id,
            'fabric_id' => $this->fabric->id,
            'control_type_id' => $this->controlType->id,
            'width' => 36,
            'height' => 48,
            'quantity' => 1,
            'markup_pct' => 50,
        ], $overrides);
    }
}
