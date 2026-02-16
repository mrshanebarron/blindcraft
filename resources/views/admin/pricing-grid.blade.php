@extends('layouts.app')
@section('title', "Pricing Grid — {$product->name}")

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.products') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; All Products</a>
        <h1 class="font-serif text-3xl mt-1">{{ $product->name }}</h1>
        <div class="flex gap-4 mt-1 text-sm text-brand-400">
            <span>{{ $product->supplier->name }}</span>
            <span>&middot; {{ $grids->count() }} grid cells</span>
            <span>&middot; {{ ucfirst($product->category) }}</span>
        </div>
    </div>

    {{-- Price Groups Legend --}}
    <div class="flex gap-3">
        @foreach(['A', 'B', 'C', 'D'] as $group)
        <div class="flex items-center gap-2 text-sm">
            <span class="w-3 h-3 rounded-sm
                {{ $group === 'A' ? 'bg-emerald-400' : '' }}
                {{ $group === 'B' ? 'bg-blue-400' : '' }}
                {{ $group === 'C' ? 'bg-amber-400' : '' }}
                {{ $group === 'D' ? 'bg-red-400' : '' }}"></span>
            <span class="font-mono text-brand-500">Group {{ $group }}</span>
        </div>
        @endforeach
    </div>

    {{-- Grid Display --}}
    @foreach(['A', 'B', 'C', 'D'] as $group)
    @php
        $groupCells = $grids->where('price_group', $group);
        $widthRanges = $groupCells->pluck('width_min', 'width_max')->unique();
        $heightRanges = $groupCells->pluck('height_min', 'height_max')->unique();
    @endphp
    @if($groupCells->count())
    <div class="card-elevated overflow-hidden">
        <div class="bg-brand-50 border-b border-brand-200 px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-sm
                    {{ $group === 'A' ? 'bg-emerald-400' : '' }}
                    {{ $group === 'B' ? 'bg-blue-400' : '' }}
                    {{ $group === 'C' ? 'bg-amber-400' : '' }}
                    {{ $group === 'D' ? 'bg-red-400' : '' }}"></span>
                <h2 class="font-semibold">Price Group {{ $group }}</h2>
            </div>
            <span class="text-xs font-mono text-brand-400">{{ $groupCells->count() }} cells</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="px-3 py-2 text-xs font-mono text-brand-400 bg-brand-50 border-b border-r border-brand-200 sticky left-0">W &darr; &times; H &rarr;</th>
                        @php
                            $heights = $groupCells->map(fn($g) => "{$g->height_min}-{$g->height_max}")->unique()->sort();
                        @endphp
                        @foreach($heights as $h)
                        <th class="px-3 py-2 text-xs font-mono text-brand-400 bg-brand-50 border-b border-brand-200 text-center whitespace-nowrap">{{ $h }}&Prime;</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        $widths = $groupCells->map(fn($g) => "{$g->width_min}-{$g->width_max}")->unique()->sort();
                    @endphp
                    @foreach($widths as $w)
                    <tr class="border-b border-brand-100">
                        <td class="px-3 py-2 text-xs font-mono text-brand-500 bg-brand-50 border-r border-brand-200 font-semibold sticky left-0 whitespace-nowrap">{{ $w }}&Prime;</td>
                        @foreach($heights as $h)
                        <td class="px-3 py-2 text-center pricing-grid-cell">
                            @if(isset($organized[$w][$h][$group]))
                            <span class="text-sm font-mono font-medium">${{ number_format($organized[$w][$h][$group], 0) }}</span>
                            @else
                            <span class="text-brand-200">&mdash;</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach

    {{-- Product Details --}}
    <div class="card-elevated p-6">
        <h2 class="font-semibold text-lg mb-4">Product Specifications</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-xs font-mono text-brand-400 uppercase">Min Width</p>
                <p class="font-medium mt-0.5">{{ $product->min_width }}&Prime;</p>
            </div>
            <div>
                <p class="text-xs font-mono text-brand-400 uppercase">Max Width</p>
                <p class="font-medium mt-0.5">{{ $product->max_width }}&Prime;</p>
            </div>
            <div>
                <p class="text-xs font-mono text-brand-400 uppercase">Min Height</p>
                <p class="font-medium mt-0.5">{{ $product->min_height }}&Prime;</p>
            </div>
            <div>
                <p class="text-xs font-mono text-brand-400 uppercase">Max Height</p>
                <p class="font-medium mt-0.5">{{ $product->max_height }}&Prime;</p>
            </div>
        </div>
        @if($product->description)
        <p class="text-sm text-brand-500 mt-4 leading-relaxed">{{ $product->description }}</p>
        @endif
    </div>
</div>
@endsection
