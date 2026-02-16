@extends('layouts.app')
@section('title', $supplier->name)

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <a href="{{ route('admin.suppliers') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; All Suppliers</a>
        <div class="flex items-center gap-3 mt-1">
            <h1 class="font-serif text-3xl">{{ $supplier->name }}</h1>
            <span class="text-xs font-mono bg-brand-100 text-brand-600 px-2 py-0.5 rounded">{{ $supplier->code }}</span>
        </div>
    </div>

    {{-- Config Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card-elevated p-4">
            <p class="text-xs font-mono text-brand-400 uppercase">Rounding</p>
            <p class="text-lg font-semibold mt-1">{{ ucfirst(str_replace('_', ' ', $supplier->rounding_method)) }}</p>
            <p class="text-xs text-brand-400">{{ $supplier->rounding_increment }}&Prime; increment</p>
        </div>
        <div class="card-elevated p-4">
            <p class="text-xs font-mono text-brand-400 uppercase">Default Markup</p>
            <p class="text-lg font-semibold mt-1">{{ $supplier->default_markup_pct }}%</p>
        </div>
        <div class="card-elevated p-4">
            <p class="text-xs font-mono text-brand-400 uppercase">Lead Time</p>
            <p class="text-lg font-semibold mt-1">{{ $supplier->products->avg('lead_time_days') ? round($supplier->products->avg('lead_time_days')) . ' days' : '—' }}</p>
            <p class="text-xs text-brand-400">avg across products</p>
        </div>
        <div class="card-elevated p-4">
            <p class="text-xs font-mono text-brand-400 uppercase">Freight</p>
            <p class="text-lg font-semibold mt-1">${{ number_format($supplier->freight_flat ?? 0, 2) }}</p>
            <p class="text-xs text-brand-400">{{ $supplier->freight_free_above ? "Free above \${$supplier->freight_free_above}" : 'No free shipping' }}</p>
        </div>
    </div>

    {{-- Products --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Products ({{ $supplier->products->count() }})</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Category</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase">Min Size</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase">Max Size</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Base Price</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase">Lead Time</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($supplier->products as $product)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-4">
                            <p class="font-medium">{{ $product->name }}</p>
                            <p class="text-xs font-mono text-brand-400">{{ $product->code }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ ucfirst($product->category) }}</td>
                        <td class="px-6 py-4 text-sm text-center font-mono">{{ $product->min_width }}&Prime;&times;{{ $product->min_height }}&Prime;</td>
                        <td class="px-6 py-4 text-sm text-center font-mono">{{ $product->max_width }}&Prime;&times;{{ $product->max_height }}&Prime;</td>
                        <td class="px-6 py-4 text-sm text-right font-mono">${{ number_format($product->base_price, 2) }}</td>
                        <td class="px-6 py-4 text-sm text-center">{{ $product->lead_time_days }} days</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.pricing-grid', $product) }}" class="text-accent-500 hover:text-accent-600 text-sm font-medium">Pricing Grid &rarr;</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fabrics --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Fabrics ({{ $supplier->fabrics->count() }})</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($supplier->fabrics as $fabric)
            <div class="card-elevated p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg border border-brand-200 flex-shrink-0" style="background-color: {{ $fabric->color_hex ?? '#e2e8f0' }}"></div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-sm truncate">{{ $fabric->name }}</p>
                    <p class="text-xs text-brand-400">{{ $fabric->collection }} &middot; {{ ucfirst($fabric->opacity) }} &middot; Group {{ $fabric->price_group }}</p>
                </div>
                @if($fabric->price_modifier)
                <span class="text-xs font-mono {{ $fabric->price_modifier > 0 ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ $fabric->price_modifier > 0 ? '+' : '' }}{{ $fabric->price_modifier }}%
                </span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Control Types --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Control Types ({{ $supplier->controlTypes->count() }})</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Control</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Code</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Price Adder</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Price Multiplier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($supplier->controlTypes as $ct)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 font-medium text-sm">{{ $ct->name }}</td>
                        <td class="px-6 py-3 font-mono text-sm text-brand-400">{{ $ct->code }}</td>
                        <td class="px-6 py-3 text-sm text-right font-mono">{{ $ct->price_adder ? '+$'.number_format($ct->price_adder, 2) : '—' }}</td>
                        <td class="px-6 py-3 text-sm text-right font-mono">{{ $ct->price_multiplier && $ct->price_multiplier != 1 ? $ct->price_multiplier.'x' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Options --}}
    @if($supplier->productOptions->count())
    <div>
        <h2 class="font-serif text-2xl mb-4">Product Options ({{ $supplier->productOptions->count() }})</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Option</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Group</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Price Adder</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Price %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($supplier->productOptions as $opt)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 font-medium text-sm">{{ $opt->name }}</td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                {{ $opt->group === 'upgrade' ? 'bg-blue-100 text-blue-600' : '' }}
                                {{ $opt->group === 'mount' ? 'bg-purple-100 text-purple-600' : '' }}
                                {{ $opt->group === 'specialty' ? 'bg-amber-100 text-amber-600' : '' }}">
                                {{ ucfirst($opt->group) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-right font-mono">{{ $opt->price_adder ? '+$'.number_format($opt->price_adder, 2) : '—' }}</td>
                        <td class="px-6 py-3 text-sm text-right font-mono">{{ $opt->price_pct ? '+'.$opt->price_pct.'%' : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Rounding Rules --}}
    @if($supplier->roundingRules->count())
    <div>
        <h2 class="font-serif text-2xl mb-4">Rounding Rules</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Dimension</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Method</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Increment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($supplier->roundingRules as $rule)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 text-sm">{{ $rule->product ? $rule->product->name : 'All Products' }}</td>
                        <td class="px-6 py-3 text-sm font-mono">{{ $rule->dimension }}</td>
                        <td class="px-6 py-3 text-sm">{{ ucfirst(str_replace('_', ' ', $rule->method)) }}</td>
                        <td class="px-6 py-3 text-sm text-right font-mono">{{ $rule->increment }}&Prime;</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Surcharges --}}
    @if($supplier->surcharges->count())
    <div>
        <h2 class="font-serif text-2xl mb-4">Surcharges</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Trigger</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Charge</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($supplier->surcharges as $surcharge)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 text-sm">{{ $surcharge->product ? $surcharge->product->name : 'All Products' }}</td>
                        <td class="px-6 py-3 text-sm">
                            <span class="font-mono text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded">{{ $surcharge->trigger_type }}</span>
                            {{ $surcharge->trigger_dimension }} &gt; {{ $surcharge->trigger_value }}&Prime;
                        </td>
                        <td class="px-6 py-3 text-sm font-mono">
                            @if($surcharge->charge_type === 'flat') ${{ number_format($surcharge->charge_amount, 2) }}
                            @elseif($surcharge->charge_type === 'percentage') {{ $surcharge->charge_amount }}%
                            @else ${{ number_format($surcharge->charge_amount, 2) }}/{{ str_replace('per_', '', $surcharge->charge_type) }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
