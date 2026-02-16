<?php

namespace App\Http\Controllers;

use App\Models\{Supplier, Product, Quote, PricingGrid};

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'suppliers' => Supplier::active()->withCount('products')->get(),
            'productCount' => Product::active()->count(),
            'gridCells' => PricingGrid::count(),
            'quoteCount' => Quote::count(),
            'recentQuotes' => Quote::latest()->take(5)->get(),
        ]);
    }
}
