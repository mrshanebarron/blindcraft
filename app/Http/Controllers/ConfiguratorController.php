<?php

namespace App\Http\Controllers;

use App\Models\{Supplier, Product, Fabric, ControlType, ProductOption};
use App\Services\PricingEngine;
use Illuminate\Http\Request;

class ConfiguratorController extends Controller
{
    public function index()
    {
        return view('configurator', [
            'suppliers' => Supplier::active()->get(),
        ]);
    }

    public function products(Supplier $supplier)
    {
        return response()->json(
            $supplier->products()->active()->get(['id', 'name', 'code', 'category', 'min_width', 'max_width', 'min_height', 'max_height', 'description'])
        );
    }

    public function fabrics(Supplier $supplier)
    {
        return response()->json(
            $supplier->fabrics()->active()->get(['id', 'name', 'code', 'collection', 'opacity', 'color', 'color_hex', 'price_group', 'price_modifier'])
        );
    }

    public function controls(Supplier $supplier)
    {
        return response()->json(
            $supplier->controlTypes()->active()->get(['id', 'name', 'code', 'price_adder', 'price_multiplier'])
        );
    }

    public function options(Supplier $supplier)
    {
        return response()->json(
            $supplier->productOptions()->active()->get(['id', 'name', 'code', 'group', 'price_adder', 'price_pct'])
        );
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'fabric_id' => 'required|exists:fabrics,id',
            'control_type_id' => 'required|exists:control_types,id',
            'width' => 'required|numeric|min:1|max:300',
            'height' => 'required|numeric|min:1|max:300',
            'quantity' => 'nullable|integer|min:1|max:100',
            'markup_pct' => 'nullable|numeric|min:0|max:500',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'exists:product_options,id',
        ]);

        $engine = new PricingEngine();
        return response()->json($engine->calculate($validated));
    }
}
