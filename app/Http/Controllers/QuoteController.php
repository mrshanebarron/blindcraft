<?php

namespace App\Http\Controllers;

use App\Models\{Quote, QuoteLineItem, Supplier};
use App\Services\PricingEngine;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index()
    {
        return view('quotes.index', [
            'quotes' => Quote::withCount('lineItems')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $quote = Quote::create([
            'quote_number' => Quote::generateNumber(),
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_phone' => $request->input('customer_phone'),
            'project_name' => $request->input('project_name'),
            'status' => 'draft',
            'valid_until' => now()->addDays(30),
        ]);

        return redirect()->route('quotes.show', $quote)->with('success', 'Quote created.');
    }

    public function show(Quote $quote)
    {
        $quote->load('lineItems.product.supplier', 'lineItems.fabric', 'lineItems.controlType');
        return view('quotes.show', [
            'quote' => $quote,
            'suppliers' => Supplier::active()->get(),
        ]);
    }

    public function addLine(Request $request, Quote $quote)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'fabric_id' => 'required|exists:fabrics,id',
            'control_type_id' => 'required|exists:control_types,id',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1',
            'markup_pct' => 'nullable|numeric|min:0',
            'option_ids' => 'nullable|array',
            'room_name' => 'nullable|string|max:100',
            'window_name' => 'nullable|string|max:100',
            'mount_type' => 'nullable|in:inside,outside',
        ]);

        $engine = new PricingEngine();
        $result = $engine->calculate($validated);

        if (!$result['success']) {
            return back()->withErrors(['config' => implode(' ', $result['breakdown']['errors'])]);
        }

        $p = $result['pricing'];

        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'product_id' => $validated['product_id'],
            'fabric_id' => $validated['fabric_id'],
            'control_type_id' => $validated['control_type_id'],
            'room_name' => $validated['room_name'] ?? null,
            'window_name' => $validated['window_name'] ?? null,
            'width' => $validated['width'],
            'height' => $validated['height'],
            'rounded_width' => $p['rounded_width'],
            'rounded_height' => $p['rounded_height'],
            'quantity' => $p['quantity'],
            'mount_type' => $validated['mount_type'] ?? 'inside',
            'selected_options' => $validated['option_ids'] ?? [],
            'grid_cost' => $p['grid_cost'],
            'control_adder' => $p['control_adder'],
            'options_adder' => $p['options_adder'],
            'surcharges' => $p['surcharges'],
            'unit_cost' => $p['unit_cost'],
            'markup_pct' => $p['markup_pct'],
            'unit_sell' => $p['unit_sell'],
            'line_cost' => $p['line_cost'],
            'line_sell' => $p['line_sell'],
            'line_margin' => $p['line_margin'],
            'pricing_breakdown' => $result['breakdown'],
        ]);

        $quote->recalculateTotals();

        return back()->with('success', 'Line item added.');
    }

    public function removeLine(Quote $quote, QuoteLineItem $lineItem)
    {
        $lineItem->delete();
        $quote->refresh();
        $quote->recalculateTotals();
        return back()->with('success', 'Line item removed.');
    }
}
