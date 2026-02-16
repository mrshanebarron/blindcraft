@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('admin.index') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; Admin Panel</a>
        <h1 class="font-serif text-3xl mt-1">Suppliers</h1>
        <p class="text-brand-400 text-sm mt-1">Each supplier has independent pricing grids, rounding rules, and product catalogs</p>
    </div>

    <div class="grid gap-4">
        @foreach($suppliers as $supplier)
        <div class="card-elevated p-6">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-serif text-xl">{{ $supplier->name }}</h2>
                        <span class="text-xs font-mono bg-brand-100 text-brand-600 px-2 py-0.5 rounded">{{ $supplier->code }}</span>
                        @if($supplier->active)
                        <span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full font-medium">Active</span>
                        @else
                        <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-medium">Inactive</span>
                        @endif
                    </div>
                    <div class="flex gap-6 mt-3 text-sm text-brand-500">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            {{ $supplier->products_count }} products
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                            {{ $supplier->fabrics_count }} fabrics
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            {{ $supplier->control_types_count }} controls
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm text-brand-400">
                        <p>Rounding: <span class="font-mono text-brand-600">{{ $supplier->rounding_method }}</span> ({{ $supplier->rounding_increment }}&Prime;)</p>
                        <p>Default markup: <span class="font-mono text-brand-600">{{ $supplier->default_markup_pct }}%</span></p>
                    </div>
                    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="inline-flex items-center gap-1 text-accent-500 hover:text-accent-600 text-sm font-medium mt-3">
                        View Details
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
