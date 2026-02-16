@extends('layouts.app')
@section('title', 'Products')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.index') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; Admin Panel</a>
        <h1 class="font-serif text-3xl mt-1">Product Catalog</h1>
        <p class="text-brand-400 text-sm mt-1">All configurable products across suppliers with pricing grid cell counts</p>
    </div>

    <div class="card-elevated overflow-hidden">
        <table class="w-full">
            <thead class="bg-brand-50 border-b border-brand-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Product</th>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Supplier</th>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Category</th>
                    <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Size Range</th>
                    <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Base Price</th>
                    <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Grid Cells</th>
                    <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Lead Time</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-100">
                @foreach($products as $product)
                <tr class="hover:bg-brand-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-medium">{{ $product->name }}</p>
                        <p class="text-xs font-mono text-brand-400">{{ $product->code }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm">{{ $product->supplier->name }}</span>
                        <span class="text-xs font-mono text-brand-300 ml-1">({{ $product->supplier->code }})</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                            {{ $product->category === 'roller' ? 'bg-blue-100 text-blue-600' : '' }}
                            {{ $product->category === 'cellular' ? 'bg-green-100 text-green-600' : '' }}
                            {{ $product->category === 'wood' ? 'bg-amber-100 text-amber-700' : '' }}
                            {{ $product->category === 'faux_wood' ? 'bg-orange-100 text-orange-600' : '' }}
                            {{ $product->category === 'vertical' ? 'bg-purple-100 text-purple-600' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $product->category)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-center font-mono">
                        {{ $product->min_width }}&Prime;&ndash;{{ $product->max_width }}&Prime; W<br>
                        <span class="text-brand-400">{{ $product->min_height }}&Prime;&ndash;{{ $product->max_height }}&Prime; H</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-right font-mono">${{ number_format($product->base_price, 2) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-100 text-brand-700 font-mono text-sm font-semibold">
                            {{ $product->pricing_grids_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-center text-brand-500">{{ $product->lead_time_days }}d</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.pricing-grid', $product) }}" class="text-accent-500 hover:text-accent-600 text-sm font-medium">View Grid &rarr;</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
