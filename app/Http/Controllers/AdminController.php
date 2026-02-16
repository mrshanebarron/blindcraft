<?php

namespace App\Http\Controllers;

use App\Models\{Supplier, Product, PricingGrid, CompatibilityRule, Surcharge, RoundingRule};

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index', [
            'suppliers' => Supplier::withCount(['products', 'fabrics', 'controlTypes', 'surcharges'])->get(),
            'totalGridCells' => PricingGrid::count(),
            'totalRules' => CompatibilityRule::count(),
        ]);
    }

    public function suppliers()
    {
        return view('admin.suppliers', [
            'suppliers' => Supplier::withCount(['products', 'fabrics', 'controlTypes'])->get(),
        ]);
    }

    public function supplierDetail(Supplier $supplier)
    {
        $supplier->load('products', 'fabrics', 'controlTypes', 'productOptions', 'roundingRules', 'surcharges');
        return view('admin.supplier-detail', compact('supplier'));
    }

    public function products()
    {
        return view('admin.products', [
            'products' => Product::with('supplier')->withCount('pricingGrids')->get(),
        ]);
    }

    public function pricingGrid(Product $product)
    {
        $product->load('supplier');
        $grids = PricingGrid::where('product_id', $product->id)
            ->orderBy('width_min')
            ->orderBy('height_min')
            ->orderBy('price_group')
            ->get();

        // Organize into a displayable grid structure
        $organized = [];
        foreach ($grids as $g) {
            $widthKey = "{$g->width_min}-{$g->width_max}";
            $heightKey = "{$g->height_min}-{$g->height_max}";
            $organized[$widthKey][$heightKey][$g->price_group] = $g->dealer_cost;
        }

        return view('admin.pricing-grid', [
            'product' => $product,
            'organized' => $organized,
            'grids' => $grids,
        ]);
    }

    public function rules()
    {
        return view('admin.rules', [
            'compatibility' => CompatibilityRule::with('supplier', 'product')->get(),
            'surcharges' => Surcharge::with('supplier', 'product')->get(),
            'rounding' => RoundingRule::with('supplier', 'product')->get(),
        ]);
    }
}
