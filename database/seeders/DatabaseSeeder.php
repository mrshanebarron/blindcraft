<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption, PricingGrid, RoundingRule, CompatibilityRule, Surcharge};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@blindcraft.com',
            'password' => bcrypt('password'),
        ]);

        $this->seedSuppliers();
    }

    private function seedSuppliers(): void
    {
        // === SUPPLIER 1: Premier Shade Co ===
        $premier = Supplier::create([
            'name' => 'Premier Shade Co',
            'code' => 'PSC',
            'rounding_method' => 'up',
            'rounding_increment' => 0.5,
            'default_markup_pct' => 55.00,
            'freight_flat' => 35.00,
            'freight_free_above' => 500.00,
        ]);

        // Products
        $rollerPSC = Product::create([
            'supplier_id' => $premier->id,
            'name' => 'Designer Roller Shade',
            'code' => 'PSC-ROLL',
            'category' => 'roller_shades',
            'min_width' => 14,
            'max_width' => 96,
            'min_height' => 12,
            'max_height' => 120,
            'lead_time_days' => 8,
            'description' => 'Premium roller shade with cassette headrail and slow-rise spring.',
        ]);

        $cellularPSC = Product::create([
            'supplier_id' => $premier->id,
            'name' => 'Honeycomb Cellular',
            'code' => 'PSC-CELL',
            'category' => 'cellular_shades',
            'min_width' => 15,
            'max_width' => 84,
            'min_height' => 12,
            'max_height' => 96,
            'lead_time_days' => 10,
            'description' => 'Double-cell construction for superior insulation. Available in 3/8" and 3/4" pleat.',
        ]);

        $woodPSC = Product::create([
            'supplier_id' => $premier->id,
            'name' => 'Hardwood Blind',
            'code' => 'PSC-WOOD',
            'category' => 'wood_blinds',
            'min_width' => 12,
            'max_width' => 72,
            'min_height' => 12,
            'max_height' => 96,
            'lead_time_days' => 14,
            'description' => 'Genuine basswood slats in 2" or 2.5" widths. Custom stain matching available.',
        ]);

        // Fabrics for Premier
        $fabricsPSC = [
            ['name' => 'Arctic White', 'code' => 'PSC-F-AW', 'collection' => 'Essentials', 'opacity' => 'light_filtering', 'color' => 'White', 'color_hex' => '#F5F5F0', 'price_group' => 'A'],
            ['name' => 'Pearl Grey', 'code' => 'PSC-F-PG', 'collection' => 'Essentials', 'opacity' => 'light_filtering', 'color' => 'Grey', 'color_hex' => '#C4C4C4', 'price_group' => 'A'],
            ['name' => 'Linen Beige', 'code' => 'PSC-F-LB', 'collection' => 'Essentials', 'opacity' => 'light_filtering', 'color' => 'Beige', 'color_hex' => '#D4C5A9', 'price_group' => 'A'],
            ['name' => 'Charcoal Screen 3%', 'code' => 'PSC-F-CS3', 'collection' => 'Solar Screen', 'opacity' => 'sheer', 'color' => 'Charcoal', 'color_hex' => '#4A4A4A', 'price_group' => 'B'],
            ['name' => 'Bronze Screen 5%', 'code' => 'PSC-F-BS5', 'collection' => 'Solar Screen', 'opacity' => 'sheer', 'color' => 'Bronze', 'color_hex' => '#8B7355', 'price_group' => 'B'],
            ['name' => 'Midnight Blackout', 'code' => 'PSC-F-MB', 'collection' => 'Blackout Pro', 'opacity' => 'blackout', 'color' => 'Black', 'color_hex' => '#1A1A1A', 'price_group' => 'C', 'price_modifier' => 15.00],
            ['name' => 'Ivory Blackout', 'code' => 'PSC-F-IB', 'collection' => 'Blackout Pro', 'opacity' => 'blackout', 'color' => 'Ivory', 'color_hex' => '#FFFFF0', 'price_group' => 'C', 'price_modifier' => 15.00],
            ['name' => 'Walnut Stain', 'code' => 'PSC-F-WS', 'collection' => 'Wood Finishes', 'opacity' => 'room_darkening', 'color' => 'Brown', 'color_hex' => '#5C4033', 'price_group' => 'B'],
            ['name' => 'Classic Oak', 'code' => 'PSC-F-CO', 'collection' => 'Wood Finishes', 'opacity' => 'room_darkening', 'color' => 'Oak', 'color_hex' => '#C19A6B', 'price_group' => 'A'],
            ['name' => 'Designer Slate', 'code' => 'PSC-F-DS', 'collection' => 'Designer', 'opacity' => 'room_darkening', 'color' => 'Slate', 'color_hex' => '#708090', 'price_group' => 'D', 'price_modifier' => 25.00],
        ];

        foreach ($fabricsPSC as $f) {
            Fabric::create(array_merge(['supplier_id' => $premier->id], $f));
        }

        // Control Types for Premier
        $controlsPSC = [
            ['name' => 'Standard Cord', 'code' => 'CORD', 'price_adder' => 0, 'price_multiplier' => 1.000],
            ['name' => 'Cordless', 'code' => 'CORDLESS', 'price_adder' => 25.00, 'price_multiplier' => 1.000],
            ['name' => 'Continuous Loop', 'code' => 'CLOOP', 'price_adder' => 15.00, 'price_multiplier' => 1.000],
            ['name' => 'Motorized (Hardwired)', 'code' => 'MOTOR-HW', 'price_adder' => 150.00, 'price_multiplier' => 1.150],
            ['name' => 'Motorized (Battery)', 'code' => 'MOTOR-BT', 'price_adder' => 125.00, 'price_multiplier' => 1.100],
        ];

        foreach ($controlsPSC as $c) {
            ControlType::create(array_merge(['supplier_id' => $premier->id], $c));
        }

        // Options for Premier
        $optionsPSC = [
            ['name' => 'Fabric Wrapped Valance', 'code' => 'VALANCE', 'group' => 'upgrade', 'price_adder' => 35.00],
            ['name' => 'Fascia Headrail', 'code' => 'FASCIA', 'group' => 'upgrade', 'price_adder' => 28.00],
            ['name' => 'Edge Banding', 'code' => 'EDGE', 'group' => 'upgrade', 'price_adder' => 18.00],
            ['name' => 'Decorative Hem Bar', 'code' => 'HEMBAR', 'group' => 'upgrade', 'price_adder' => 12.00],
            ['name' => 'Hold-Down Brackets', 'code' => 'HOLDDOWN', 'group' => 'mount', 'price_adder' => 8.00],
            ['name' => 'Extended Brackets', 'code' => 'EXTBRACKET', 'group' => 'mount', 'price_adder' => 15.00],
        ];

        foreach ($optionsPSC as $o) {
            ProductOption::create(array_merge(['supplier_id' => $premier->id], $o));
        }

        // Pricing Grids for Premier - Roller Shade
        $this->seedPricingGrid($rollerPSC->id, [
            // width ranges → height ranges → [A, B, C, D prices]
            [[14, 24], [12, 36], [89, 105, 119, 145]],
            [[14, 24], [36, 48], [99, 115, 132, 159]],
            [[14, 24], [48, 60], [109, 129, 148, 178]],
            [[14, 24], [60, 72], [119, 139, 159, 195]],
            [[14, 24], [72, 84], [135, 155, 179, 215]],
            [[14, 24], [84, 96], [149, 172, 198, 239]],
            [[14, 24], [96, 120], [169, 195, 225, 269]],
            [[24, 36], [12, 36], [99, 115, 132, 159]],
            [[24, 36], [36, 48], [115, 135, 155, 185]],
            [[24, 36], [48, 60], [129, 152, 175, 209]],
            [[24, 36], [60, 72], [145, 169, 195, 235]],
            [[24, 36], [72, 84], [162, 189, 218, 262]],
            [[24, 36], [84, 96], [179, 209, 242, 289]],
            [[24, 36], [96, 120], [199, 235, 272, 325]],
            [[36, 48], [12, 36], [115, 135, 155, 185]],
            [[36, 48], [36, 48], [135, 159, 182, 219]],
            [[36, 48], [48, 60], [155, 182, 209, 252]],
            [[36, 48], [60, 72], [175, 205, 235, 285]],
            [[36, 48], [72, 84], [195, 229, 265, 319]],
            [[36, 48], [84, 96], [219, 255, 295, 355]],
            [[36, 48], [96, 120], [245, 289, 335, 399]],
            [[48, 60], [12, 36], [135, 159, 182, 219]],
            [[48, 60], [36, 48], [159, 185, 215, 259]],
            [[48, 60], [48, 60], [182, 215, 248, 299]],
            [[48, 60], [60, 72], [209, 245, 282, 339]],
            [[48, 60], [72, 84], [235, 275, 318, 382]],
            [[48, 60], [84, 96], [262, 309, 355, 429]],
            [[48, 60], [96, 120], [295, 348, 399, 479]],
            [[60, 72], [12, 36], [159, 185, 215, 259]],
            [[60, 72], [36, 48], [185, 219, 252, 305]],
            [[60, 72], [48, 60], [215, 252, 292, 349]],
            [[60, 72], [60, 72], [245, 289, 335, 399]],
            [[60, 72], [72, 84], [278, 325, 375, 449]],
            [[60, 72], [84, 96], [312, 365, 419, 505]],
            [[60, 72], [96, 120], [349, 409, 469, 565]],
            [[72, 96], [12, 36], [185, 219, 252, 305]],
            [[72, 96], [36, 48], [219, 259, 298, 359]],
            [[72, 96], [48, 60], [259, 305, 349, 419]],
            [[72, 96], [60, 72], [298, 349, 399, 479]],
            [[72, 96], [72, 84], [339, 398, 455, 545]],
            [[72, 96], [84, 96], [379, 445, 509, 615]],
            [[72, 96], [96, 120], [425, 498, 575, 689]],
        ]);

        // Pricing Grid for Cellular
        $this->seedPricingGrid($cellularPSC->id, [
            [[15, 24], [12, 36], [109, 125, 145, 175]],
            [[15, 24], [36, 48], [125, 145, 165, 199]],
            [[15, 24], [48, 60], [142, 165, 189, 229]],
            [[15, 24], [60, 72], [159, 185, 215, 259]],
            [[15, 24], [72, 96], [179, 209, 242, 289]],
            [[24, 36], [12, 36], [125, 145, 165, 199]],
            [[24, 36], [36, 48], [149, 172, 198, 239]],
            [[24, 36], [48, 60], [172, 199, 229, 275]],
            [[24, 36], [60, 72], [195, 228, 262, 315]],
            [[24, 36], [72, 96], [222, 259, 298, 359]],
            [[36, 48], [12, 36], [145, 169, 195, 235]],
            [[36, 48], [36, 48], [175, 205, 235, 285]],
            [[36, 48], [48, 60], [205, 239, 275, 332]],
            [[36, 48], [60, 72], [235, 275, 318, 382]],
            [[36, 48], [72, 96], [268, 315, 362, 435]],
            [[48, 60], [12, 36], [169, 198, 228, 275]],
            [[48, 60], [36, 48], [205, 239, 275, 332]],
            [[48, 60], [48, 60], [242, 282, 325, 392]],
            [[48, 60], [60, 72], [278, 325, 375, 449]],
            [[48, 60], [72, 96], [319, 372, 429, 515]],
            [[60, 84], [12, 36], [198, 232, 268, 322]],
            [[60, 84], [36, 48], [242, 282, 325, 392]],
            [[60, 84], [48, 60], [285, 335, 385, 462]],
            [[60, 84], [60, 72], [329, 385, 445, 535]],
            [[60, 84], [72, 96], [378, 442, 509, 612]],
        ]);

        // Pricing Grid for Wood Blinds
        $this->seedPricingGrid($woodPSC->id, [
            [[12, 24], [12, 36], [119, 139, 159, 195]],
            [[12, 24], [36, 48], [139, 162, 185, 225]],
            [[12, 24], [48, 60], [162, 189, 218, 265]],
            [[12, 24], [60, 72], [185, 218, 252, 305]],
            [[12, 24], [72, 96], [215, 252, 292, 349]],
            [[24, 36], [12, 36], [145, 169, 195, 235]],
            [[24, 36], [36, 48], [172, 199, 229, 275]],
            [[24, 36], [48, 60], [199, 232, 268, 322]],
            [[24, 36], [60, 72], [229, 268, 309, 372]],
            [[24, 36], [72, 96], [265, 309, 355, 429]],
            [[36, 48], [12, 36], [175, 205, 235, 285]],
            [[36, 48], [36, 48], [209, 245, 282, 339]],
            [[36, 48], [48, 60], [245, 285, 329, 395]],
            [[36, 48], [60, 72], [282, 329, 378, 455]],
            [[36, 48], [72, 96], [325, 378, 435, 525]],
            [[48, 60], [12, 36], [209, 245, 282, 339]],
            [[48, 60], [36, 48], [252, 295, 339, 409]],
            [[48, 60], [48, 60], [295, 345, 398, 479]],
            [[48, 60], [60, 72], [342, 398, 459, 552]],
            [[48, 60], [72, 96], [392, 458, 525, 635]],
            [[60, 72], [12, 36], [245, 289, 335, 399]],
            [[60, 72], [36, 48], [298, 349, 399, 479]],
            [[60, 72], [48, 60], [349, 409, 469, 565]],
            [[60, 72], [60, 72], [399, 469, 539, 649]],
            [[60, 72], [72, 96], [459, 535, 619, 745]],
        ]);

        // Rounding Rules
        RoundingRule::create([
            'supplier_id' => $premier->id,
            'product_id' => null,
            'dimension' => 'both',
            'method' => 'up',
            'increment' => 0.5,
            'notes' => 'Premier rounds all dimensions up to nearest 1/2 inch',
        ]);

        // Surcharges for Premier
        Surcharge::create([
            'supplier_id' => $premier->id,
            'name' => 'Oversize Surcharge',
            'trigger_type' => 'oversize',
            'trigger_value' => 200,
            'trigger_dimension' => 'united_inches',
            'charge_type' => 'flat',
            'charge_amount' => 45.00,
        ]);

        Surcharge::create([
            'supplier_id' => $premier->id,
            'name' => 'Wide Width Premium',
            'trigger_type' => 'oversize',
            'trigger_value' => 72,
            'trigger_dimension' => 'width',
            'charge_type' => 'percentage',
            'charge_amount' => 8.00,
        ]);

        // === SUPPLIER 2: Vista Window Systems ===
        $vista = Supplier::create([
            'name' => 'Vista Window Systems',
            'code' => 'VWS',
            'rounding_method' => 'nearest',
            'rounding_increment' => 1.0,
            'default_markup_pct' => 50.00,
            'freight_flat' => 25.00,
            'freight_pct' => 3.50,
            'freight_free_above' => 750.00,
        ]);

        $rollerVWS = Product::create([
            'supplier_id' => $vista->id,
            'name' => 'EcoView Roller',
            'code' => 'VWS-ECO',
            'category' => 'roller_shades',
            'min_width' => 12,
            'max_width' => 120,
            'min_height' => 12,
            'max_height' => 132,
            'lead_time_days' => 6,
            'description' => 'Budget-friendly roller shade with clutch or spring operation.',
        ]);

        $fauxWoodVWS = Product::create([
            'supplier_id' => $vista->id,
            'name' => 'Faux Wood Classic',
            'code' => 'VWS-FAUX',
            'category' => 'faux_wood',
            'min_width' => 12,
            'max_width' => 72,
            'min_height' => 12,
            'max_height' => 84,
            'lead_time_days' => 7,
            'description' => 'Moisture-resistant PVC slats. Perfect for kitchens and bathrooms.',
        ]);

        $verticalVWS = Product::create([
            'supplier_id' => $vista->id,
            'name' => 'Vertical Traverse',
            'code' => 'VWS-VERT',
            'category' => 'verticals',
            'min_width' => 24,
            'max_width' => 192,
            'min_height' => 24,
            'max_height' => 120,
            'lead_time_days' => 8,
            'description' => 'PVC or fabric vertical blinds for sliding doors and large windows.',
        ]);

        // Vista Fabrics
        $fabricsVWS = [
            ['name' => 'Snow White', 'code' => 'VWS-F-SW', 'collection' => 'Value', 'opacity' => 'light_filtering', 'color' => 'White', 'color_hex' => '#FAFAFA', 'price_group' => 'A'],
            ['name' => 'Cream', 'code' => 'VWS-F-CR', 'collection' => 'Value', 'opacity' => 'light_filtering', 'color' => 'Cream', 'color_hex' => '#FFFDD0', 'price_group' => 'A'],
            ['name' => 'Dove Grey', 'code' => 'VWS-F-DG', 'collection' => 'Value', 'opacity' => 'light_filtering', 'color' => 'Grey', 'color_hex' => '#B0B0B0', 'price_group' => 'A'],
            ['name' => 'Mocha Screen', 'code' => 'VWS-F-MS', 'collection' => 'Screen', 'opacity' => 'sheer', 'color' => 'Brown', 'color_hex' => '#6F4E37', 'price_group' => 'B'],
            ['name' => 'Graphite Screen', 'code' => 'VWS-F-GS', 'collection' => 'Screen', 'opacity' => 'sheer', 'color' => 'Graphite', 'color_hex' => '#383838', 'price_group' => 'B'],
            ['name' => 'Total Blackout White', 'code' => 'VWS-F-TBW', 'collection' => 'Total Blackout', 'opacity' => 'blackout', 'color' => 'White', 'color_hex' => '#F0F0F0', 'price_group' => 'C', 'price_modifier' => 12.00],
            ['name' => 'Pure White PVC', 'code' => 'VWS-F-PWP', 'collection' => 'Faux Wood', 'opacity' => 'room_darkening', 'color' => 'White', 'color_hex' => '#FFFFFF', 'price_group' => 'A'],
            ['name' => 'Espresso PVC', 'code' => 'VWS-F-EP', 'collection' => 'Faux Wood', 'opacity' => 'room_darkening', 'color' => 'Espresso', 'color_hex' => '#3C1414', 'price_group' => 'B'],
        ];

        foreach ($fabricsVWS as $f) {
            Fabric::create(array_merge(['supplier_id' => $vista->id], $f));
        }

        // Vista Controls
        $controlsVWS = [
            ['name' => 'Standard Cord', 'code' => 'CORD', 'price_adder' => 0, 'price_multiplier' => 1.000],
            ['name' => 'Cordless Lift', 'code' => 'CORDLESS', 'price_adder' => 20.00, 'price_multiplier' => 1.000],
            ['name' => 'Wand Tilt', 'code' => 'WAND', 'price_adder' => 0, 'price_multiplier' => 1.000],
            ['name' => 'Motorized Smart', 'code' => 'MOTOR-SM', 'price_adder' => 175.00, 'price_multiplier' => 1.200],
        ];

        foreach ($controlsVWS as $c) {
            ControlType::create(array_merge(['supplier_id' => $vista->id], $c));
        }

        // Vista Options
        $optionsVWS = [
            ['name' => 'Cassette Headrail', 'code' => 'CASSETTE', 'group' => 'upgrade', 'price_adder' => 22.00],
            ['name' => 'Fabric Valance', 'code' => 'VALANCE', 'group' => 'upgrade', 'price_adder' => 30.00],
            ['name' => 'Cloth Tape Upgrade', 'code' => 'CLOTHTAPE', 'group' => 'upgrade', 'price_adder' => 18.00],
            ['name' => 'Extension Brackets (2")', 'code' => 'EXTBKT2', 'group' => 'mount', 'price_adder' => 10.00],
        ];

        foreach ($optionsVWS as $o) {
            ProductOption::create(array_merge(['supplier_id' => $vista->id], $o));
        }

        // Vista Pricing Grids
        $this->seedPricingGrid($rollerVWS->id, [
            [[12, 24], [12, 36], [69, 82, 95, 115]],
            [[12, 24], [36, 48], [79, 95, 109, 132]],
            [[12, 24], [48, 60], [89, 105, 122, 148]],
            [[12, 24], [60, 72], [99, 118, 135, 165]],
            [[12, 24], [72, 96], [115, 135, 155, 189]],
            [[12, 24], [96, 132], [132, 155, 179, 215]],
            [[24, 36], [12, 36], [82, 95, 109, 132]],
            [[24, 36], [36, 48], [95, 112, 129, 155]],
            [[24, 36], [48, 60], [109, 129, 148, 179]],
            [[24, 36], [60, 72], [122, 145, 165, 199]],
            [[24, 36], [72, 96], [139, 165, 189, 228]],
            [[24, 36], [96, 132], [159, 189, 218, 262]],
            [[36, 48], [12, 36], [95, 112, 129, 155]],
            [[36, 48], [36, 48], [112, 132, 152, 185]],
            [[36, 48], [48, 60], [132, 155, 179, 215]],
            [[36, 48], [60, 72], [148, 175, 199, 242]],
            [[36, 48], [72, 96], [168, 198, 228, 275]],
            [[36, 48], [96, 132], [192, 225, 259, 312]],
            [[48, 72], [12, 36], [115, 135, 155, 189]],
            [[48, 72], [36, 48], [135, 159, 185, 222]],
            [[48, 72], [48, 60], [159, 189, 218, 262]],
            [[48, 72], [60, 72], [182, 215, 248, 299]],
            [[48, 72], [72, 96], [209, 245, 282, 342]],
            [[48, 72], [96, 132], [239, 282, 325, 392]],
            [[72, 96], [12, 36], [142, 168, 192, 232]],
            [[72, 96], [36, 48], [168, 198, 228, 275]],
            [[72, 96], [48, 60], [198, 232, 268, 322]],
            [[72, 96], [60, 72], [228, 268, 309, 372]],
            [[72, 96], [72, 96], [262, 309, 355, 429]],
            [[72, 96], [96, 132], [299, 352, 405, 489]],
            [[96, 120], [12, 36], [172, 202, 232, 282]],
            [[96, 120], [36, 48], [205, 242, 278, 335]],
            [[96, 120], [48, 60], [242, 285, 328, 395]],
            [[96, 120], [60, 72], [278, 328, 375, 452]],
            [[96, 120], [72, 96], [319, 375, 432, 519]],
            [[96, 120], [96, 132], [365, 429, 495, 595]],
        ]);

        $this->seedPricingGrid($fauxWoodVWS->id, [
            [[12, 24], [12, 36], [59, 69, 79, 95]],
            [[12, 24], [36, 48], [69, 82, 95, 115]],
            [[12, 24], [48, 60], [82, 95, 109, 132]],
            [[12, 24], [60, 84], [95, 112, 129, 155]],
            [[24, 36], [12, 36], [72, 85, 98, 118]],
            [[24, 36], [36, 48], [89, 105, 122, 148]],
            [[24, 36], [48, 60], [105, 125, 145, 175]],
            [[24, 36], [60, 84], [125, 148, 172, 209]],
            [[36, 48], [12, 36], [89, 105, 122, 148]],
            [[36, 48], [36, 48], [112, 132, 152, 185]],
            [[36, 48], [48, 60], [135, 159, 185, 222]],
            [[36, 48], [60, 84], [159, 189, 218, 265]],
            [[48, 60], [12, 36], [109, 129, 148, 179]],
            [[48, 60], [36, 48], [139, 165, 189, 228]],
            [[48, 60], [48, 60], [168, 198, 228, 275]],
            [[48, 60], [60, 84], [199, 235, 272, 328]],
            [[60, 72], [12, 36], [132, 155, 179, 215]],
            [[60, 72], [36, 48], [168, 198, 228, 275]],
            [[60, 72], [48, 60], [205, 242, 278, 335]],
            [[60, 72], [60, 84], [245, 289, 332, 399]],
        ]);

        $this->seedPricingGrid($verticalVWS->id, [
            [[24, 48], [24, 48], [89, 105, 122, 148]],
            [[24, 48], [48, 72], [105, 125, 145, 175]],
            [[24, 48], [72, 96], [125, 148, 172, 209]],
            [[24, 48], [96, 120], [148, 175, 199, 242]],
            [[48, 72], [24, 48], [109, 129, 148, 179]],
            [[48, 72], [48, 72], [135, 159, 185, 222]],
            [[48, 72], [72, 96], [162, 192, 222, 268]],
            [[48, 72], [96, 120], [192, 228, 262, 319]],
            [[72, 96], [24, 48], [135, 159, 185, 222]],
            [[72, 96], [48, 72], [168, 198, 228, 275]],
            [[72, 96], [72, 96], [205, 242, 278, 335]],
            [[72, 96], [96, 120], [245, 289, 332, 399]],
            [[96, 144], [24, 48], [168, 198, 228, 275]],
            [[96, 144], [48, 72], [209, 245, 282, 342]],
            [[96, 144], [72, 96], [255, 299, 345, 415]],
            [[96, 144], [96, 120], [305, 359, 412, 498]],
            [[144, 192], [24, 48], [209, 245, 282, 342]],
            [[144, 192], [48, 72], [262, 309, 355, 429]],
            [[144, 192], [72, 96], [319, 375, 432, 519]],
            [[144, 192], [96, 120], [382, 449, 515, 622]],
        ]);

        // Vista Rounding
        RoundingRule::create([
            'supplier_id' => $vista->id,
            'dimension' => 'both',
            'method' => 'nearest',
            'increment' => 1.0,
            'notes' => 'Vista rounds to nearest whole inch',
        ]);

        // Vista Surcharges
        Surcharge::create([
            'supplier_id' => $vista->id,
            'name' => 'Oversize Processing',
            'trigger_type' => 'oversize',
            'trigger_value' => 180,
            'trigger_dimension' => 'united_inches',
            'charge_type' => 'flat',
            'charge_amount' => 35.00,
        ]);

        // Compatibility Rules
        $motorHW = ControlType::where('code', 'MOTOR-HW')->where('supplier_id', $premier->id)->first();
        $motorBT = ControlType::where('code', 'MOTOR-BT')->where('supplier_id', $premier->id)->first();
        $blackoutMidnight = Fabric::where('code', 'PSC-F-MB')->first();

        if ($motorHW) {
            CompatibilityRule::create([
                'supplier_id' => $premier->id,
                'product_id' => $woodPSC->id,
                'rule_type' => 'excludes',
                'subject_type' => 'control_type',
                'subject_id' => $motorHW->id,
                'target_type' => 'control_type',
                'target_id' => $motorHW->id,
                'message' => 'Hardwired motors are not available for wood blinds. Use battery-powered instead.',
            ]);
        }

        if ($motorBT && $blackoutMidnight) {
            CompatibilityRule::create([
                'supplier_id' => $premier->id,
                'product_id' => $rollerPSC->id,
                'rule_type' => 'max_size_with',
                'subject_type' => 'control_type',
                'subject_id' => $motorBT->id,
                'target_type' => 'dimension',
                'target_id' => null,
                'target_value' => 84,
                'message' => 'Battery motorized rollers cannot exceed 84" in any dimension. Use hardwired for larger sizes.',
            ]);
        }
    }

    private function seedPricingGrid(int $productId, array $data): void
    {
        $groups = ['A', 'B', 'C', 'D'];
        foreach ($data as $row) {
            [$widthRange, $heightRange, $prices] = $row;
            foreach ($prices as $i => $price) {
                PricingGrid::create([
                    'product_id' => $productId,
                    'width_min' => $widthRange[0],
                    'width_max' => $widthRange[1],
                    'height_min' => $heightRange[0],
                    'height_max' => $heightRange[1],
                    'price_group' => $groups[$i],
                    'dealer_cost' => $price,
                ]);
            }
        }
    }
}
