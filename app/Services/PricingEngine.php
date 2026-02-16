<?php

namespace App\Services;

use App\Models\{Product, Fabric, ControlType, ProductOption, PricingGrid, RoundingRule, CompatibilityRule, Surcharge};

class PricingEngine
{
    public function calculate(array $config): array
    {
        $product = Product::with('supplier')->findOrFail($config['product_id']);
        $fabric = Fabric::findOrFail($config['fabric_id']);
        $controlType = ControlType::findOrFail($config['control_type_id']);
        $optionIds = $config['option_ids'] ?? [];
        $width = (float) $config['width'];
        $height = (float) $config['height'];
        $quantity = max(1, (int) ($config['quantity'] ?? 1));
        $markupPct = (float) ($config['markup_pct'] ?? $product->supplier->default_markup_pct);

        $breakdown = [
            'input' => compact('width', 'height', 'quantity', 'markupPct'),
            'steps' => [],
            'errors' => [],
            'warnings' => [],
        ];

        // 1. Validate dimensions
        $dimErrors = $product->validateDimensions($width, $height);
        if (!empty($dimErrors)) {
            $breakdown['errors'] = $dimErrors;
            return ['success' => false, 'breakdown' => $breakdown];
        }

        // 2. Check compatibility rules
        $compatErrors = $this->checkCompatibility($product, $fabric, $controlType, $optionIds, $width, $height);
        if (!empty($compatErrors)) {
            $breakdown['errors'] = $compatErrors;
            return ['success' => false, 'breakdown' => $breakdown];
        }

        // 3. Apply rounding
        $rounded = $this->applyRounding($product, $width, $height);
        $roundedWidth = $rounded['width'];
        $roundedHeight = $rounded['height'];
        $breakdown['steps'][] = [
            'step' => 'rounding',
            'original' => ['width' => $width, 'height' => $height],
            'rounded' => ['width' => $roundedWidth, 'height' => $roundedHeight],
            'method' => $rounded['method'],
        ];

        // 4. Grid lookup
        $gridPrice = $this->lookupGrid($product, $fabric, $roundedWidth, $roundedHeight);
        if ($gridPrice === null) {
            $breakdown['errors'][] = "No pricing found for {$roundedWidth}\" x {$roundedHeight}\" in price group {$fabric->price_group}";
            return ['success' => false, 'breakdown' => $breakdown];
        }
        $breakdown['steps'][] = [
            'step' => 'grid_lookup',
            'price_group' => $fabric->price_group,
            'dimensions' => "{$roundedWidth}\" x {$roundedHeight}\"",
            'dealer_cost' => $gridPrice,
        ];

        // 5. Add fabric modifier
        $fabricModifier = (float) $fabric->price_modifier;
        $afterFabric = $gridPrice + $fabricModifier;
        $breakdown['steps'][] = [
            'step' => 'fabric_modifier',
            'modifier' => $fabricModifier,
            'subtotal' => $afterFabric,
        ];

        // 6. Control type pricing
        $controlAdder = (float) $controlType->price_adder;
        $controlMultiplier = (float) $controlType->price_multiplier;
        $afterControl = ($afterFabric * $controlMultiplier) + $controlAdder;
        $breakdown['steps'][] = [
            'step' => 'control_type',
            'name' => $controlType->name,
            'adder' => $controlAdder,
            'multiplier' => $controlMultiplier,
            'subtotal' => $afterControl,
        ];

        // 7. Options
        $optionsTotal = 0;
        $optionDetails = [];
        if (!empty($optionIds)) {
            $options = ProductOption::whereIn('id', $optionIds)->get();
            foreach ($options as $opt) {
                $optCost = $opt->price_adder + ($afterControl * $opt->price_pct / 100);
                $optionsTotal += $optCost;
                $optionDetails[] = [
                    'id' => $opt->id,
                    'name' => $opt->name,
                    'adder' => $opt->price_adder,
                    'pct' => $opt->price_pct,
                    'cost' => round($optCost, 2),
                ];
            }
        }
        $afterOptions = $afterControl + $optionsTotal;
        $breakdown['steps'][] = [
            'step' => 'options',
            'options' => $optionDetails,
            'total_options' => round($optionsTotal, 2),
            'subtotal' => round($afterOptions, 2),
        ];

        // 8. Surcharges
        $surchargeTotal = $this->calculateSurcharges($product, $roundedWidth, $roundedHeight, $afterOptions);
        $unitCost = $afterOptions + $surchargeTotal['total'];
        $breakdown['steps'][] = [
            'step' => 'surcharges',
            'applied' => $surchargeTotal['details'],
            'total_surcharges' => round($surchargeTotal['total'], 2),
            'unit_cost' => round($unitCost, 2),
        ];

        // 9. Markup
        $unitSell = round($unitCost * (1 + $markupPct / 100), 2);
        $unitMargin = $unitSell - $unitCost;
        $breakdown['steps'][] = [
            'step' => 'markup',
            'markup_pct' => $markupPct,
            'unit_cost' => round($unitCost, 2),
            'unit_sell' => $unitSell,
            'unit_margin' => round($unitMargin, 2),
        ];

        // 10. Line totals
        $lineCost = round($unitCost * $quantity, 2);
        $lineSell = round($unitSell * $quantity, 2);
        $lineMargin = $lineSell - $lineCost;

        return [
            'success' => true,
            'pricing' => [
                'rounded_width' => $roundedWidth,
                'rounded_height' => $roundedHeight,
                'grid_cost' => round($gridPrice, 2),
                'control_adder' => round(($afterControl - $afterFabric), 2),
                'options_adder' => round($optionsTotal, 2),
                'surcharges' => round($surchargeTotal['total'], 2),
                'unit_cost' => round($unitCost, 2),
                'markup_pct' => $markupPct,
                'unit_sell' => $unitSell,
                'quantity' => $quantity,
                'line_cost' => $lineCost,
                'line_sell' => $lineSell,
                'line_margin' => $lineMargin,
                'margin_pct' => $lineSell > 0 ? round(($lineMargin / $lineSell) * 100, 1) : 0,
            ],
            'breakdown' => $breakdown,
        ];
    }

    private function applyRounding(Product $product, float $width, float $height): array
    {
        $supplier = $product->supplier;

        // Check for product-specific rule first, then supplier default
        $rule = RoundingRule::where('supplier_id', $supplier->id)
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)->orWhereNull('product_id');
            })
            ->orderByRaw('product_id IS NULL') // product-specific first
            ->first();

        $method = $rule ? $rule->method : $supplier->rounding_method;
        $increment = $rule ? (float) $rule->increment : (float) $supplier->rounding_increment;

        return [
            'width' => $this->roundValue($width, $method, $increment),
            'height' => $this->roundValue($height, $method, $increment),
            'method' => $method,
            'increment' => $increment,
        ];
    }

    private function roundValue(float $value, string $method, float $increment): float
    {
        if ($increment <= 0) return $value;

        return match ($method) {
            'up' => ceil($value / $increment) * $increment,
            'down' => floor($value / $increment) * $increment,
            'nearest' => round($value / $increment) * $increment,
            'next_half' => ceil($value * 2) / 2,
            'next_whole' => ceil($value),
            default => ceil($value / $increment) * $increment,
        };
    }

    private function lookupGrid(Product $product, Fabric $fabric, float $width, float $height): ?float
    {
        $grid = PricingGrid::where('product_id', $product->id)
            ->forDimensions($width, $height)
            ->forPriceGroup($fabric->price_group)
            ->first();

        return $grid ? (float) $grid->dealer_cost : null;
    }

    private function checkCompatibility(Product $product, Fabric $fabric, ControlType $controlType, array $optionIds, float $width, float $height): array
    {
        $errors = [];
        $rules = CompatibilityRule::where('supplier_id', $product->supplier_id)
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)->orWhereNull('product_id');
            })
            ->active()
            ->get();

        foreach ($rules as $rule) {
            $subjectMatch = $this->matchesSubject($rule, $fabric, $controlType, $optionIds);
            if (!$subjectMatch) continue;

            $violated = match ($rule->rule_type) {
                'excludes' => $this->matchesTarget($rule, $fabric, $controlType, $optionIds),
                'requires' => !$this->matchesTarget($rule, $fabric, $controlType, $optionIds),
                'max_size_with' => $this->exceedsDimension($rule, $width, $height),
                'min_size_with' => $this->belowDimension($rule, $width, $height),
                default => false,
            };

            if ($violated) {
                $errors[] = $rule->message;
            }
        }

        return $errors;
    }

    private function matchesSubject(CompatibilityRule $rule, Fabric $fabric, ControlType $controlType, array $optionIds): bool
    {
        return match ($rule->subject_type) {
            'fabric' => $rule->subject_id === $fabric->id,
            'control_type' => $rule->subject_id === $controlType->id,
            'option' => in_array($rule->subject_id, $optionIds),
            default => false,
        };
    }

    private function matchesTarget(CompatibilityRule $rule, Fabric $fabric, ControlType $controlType, array $optionIds): bool
    {
        return match ($rule->target_type) {
            'fabric' => $rule->target_id === $fabric->id,
            'control_type' => $rule->target_id === $controlType->id,
            'option' => in_array($rule->target_id, $optionIds),
            default => false,
        };
    }

    private function exceedsDimension(CompatibilityRule $rule, float $width, float $height): bool
    {
        $value = $rule->target_value;
        return match ($rule->target_type) {
            'dimension' => match ($rule->target_type) {
                default => ($width > $value || $height > $value),
            },
            default => false,
        };
    }

    private function belowDimension(CompatibilityRule $rule, float $width, float $height): bool
    {
        $value = $rule->target_value;
        return ($width < $value || $height < $value);
    }

    private function calculateSurcharges(Product $product, float $width, float $height, float $basePrice): array
    {
        $surcharges = Surcharge::where('supplier_id', $product->supplier_id)
            ->where(function ($q) use ($product) {
                $q->where('product_id', $product->id)->orWhereNull('product_id');
            })
            ->active()
            ->get();

        $total = 0;
        $details = [];
        $unitedInches = $width + $height;
        $sqft = ($width * $height) / 144;

        foreach ($surcharges as $surcharge) {
            $triggered = $this->isSurchargeTriggered($surcharge, $width, $height, $unitedInches, $sqft);
            if (!$triggered) continue;

            $amount = match ($surcharge->charge_type) {
                'flat' => (float) $surcharge->charge_amount,
                'percentage' => $basePrice * (float) $surcharge->charge_amount / 100,
                'per_sqft' => $sqft * (float) $surcharge->charge_amount,
                'per_united_inch' => $unitedInches * (float) $surcharge->charge_amount,
                default => 0,
            };

            $total += $amount;
            $details[] = [
                'name' => $surcharge->name,
                'trigger' => $surcharge->trigger_type,
                'charge_type' => $surcharge->charge_type,
                'amount' => round($amount, 2),
            ];
        }

        return ['total' => $total, 'details' => $details];
    }

    private function isSurchargeTriggered(Surcharge $surcharge, float $width, float $height, float $unitedInches, float $sqft): bool
    {
        $threshold = (float) $surcharge->trigger_value;
        $dim = $surcharge->trigger_dimension;

        return match ($surcharge->trigger_type) {
            'oversize' => match ($dim) {
                'width' => $width > $threshold,
                'height' => $height > $threshold,
                'united_inches' => $unitedInches > $threshold,
                'sqft' => $sqft > $threshold,
                default => false,
            },
            'undersize' => match ($dim) {
                'width' => $width < $threshold,
                'height' => $height < $threshold,
                'sqft' => $sqft < $threshold,
                default => false,
            },
            'rush', 'custom', 'specialty_shape' => true, // always applied when present
            default => false,
        };
    }
}
