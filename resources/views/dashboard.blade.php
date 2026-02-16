@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-xl bg-brand-950 text-white p-8 md:p-12">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-accent-500 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-brand-500 rounded-full blur-3xl translate-y-1/3 -translate-x-1/4"></div>
        </div>
        <div class="relative">
            <p class="text-accent-400 font-mono text-sm tracking-widest uppercase mb-3">Configure &middot; Price &middot; Quote</p>
            <h1 class="font-serif text-4xl md:text-5xl mb-4">BlindCraft CPQ</h1>
            <p class="text-brand-200 text-lg max-w-2xl leading-relaxed">Configurable rule-based pricing engine for custom window coverings. Multi-supplier support with independent pricing grids, rounding rules, compatibility logic, and margin management.</p>
            <div class="mt-8 flex gap-4">
                <a href="{{ route('configurator') }}" class="inline-flex items-center gap-2 bg-accent-500 hover:bg-accent-600 text-white font-semibold px-6 py-3 rounded-lg transition-all hover:shadow-lg hover:shadow-accent-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    Open Configurator
                </a>
                <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-2 border border-brand-600 hover:border-brand-400 text-brand-200 hover:text-white font-medium px-6 py-3 rounded-lg transition-all">
                    View Admin Panel
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Suppliers</p>
            <p class="text-3xl font-serif mt-1">{{ $suppliers->count() }}</p>
            <p class="text-brand-400 text-sm mt-1">Active</p>
        </div>
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Products</p>
            <p class="text-3xl font-serif mt-1">{{ $productCount }}</p>
            <p class="text-brand-400 text-sm mt-1">Configurable</p>
        </div>
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Price Points</p>
            <p class="text-3xl font-serif mt-1">{{ number_format($gridCells) }}</p>
            <p class="text-brand-400 text-sm mt-1">Grid Cells</p>
        </div>
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Quotes</p>
            <p class="text-3xl font-serif mt-1">{{ $quoteCount }}</p>
            <p class="text-brand-400 text-sm mt-1">Generated</p>
        </div>
    </div>

    {{-- Suppliers --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Supplier Catalog</h2>
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($suppliers as $supplier)
            <div class="card-elevated p-6 flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-lg">{{ $supplier->name }}</h3>
                    <p class="font-mono text-xs text-brand-400 mt-0.5">{{ $supplier->code }}</p>
                    <div class="flex gap-4 mt-3 text-sm text-brand-500">
                        <span>{{ $supplier->products_count }} products</span>
                        <span>&middot;</span>
                        <span>{{ $supplier->rounding_method }} rounding</span>
                        <span>&middot;</span>
                        <span>{{ $supplier->default_markup_pct }}% default markup</span>
                    </div>
                </div>
                <a href="{{ route('admin.suppliers.show', $supplier) }}" class="text-brand-400 hover:text-accent-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Architecture Overview --}}
    <div class="card-elevated p-8">
        <h2 class="font-serif text-2xl mb-6">Engine Architecture</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-accent-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <h3 class="font-semibold">Pricing Grid Lookup</h3>
                </div>
                <p class="text-sm text-brand-500 leading-relaxed">Width &times; Height &times; Price Group matrix. Each product has its own grid per supplier. Dimensions are rounded per supplier rules before lookup.</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-accent-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <h3 class="font-semibold">Compatibility Rules</h3>
                </div>
                <p class="text-sm text-brand-500 leading-relaxed">Enforces valid configurations: fabric + control type + option combos. Prevents ordering motors on unsupported products, oversized motorized shades, etc.</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-2 text-accent-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <h3 class="font-semibold">Margin Management</h3>
                </div>
                <p class="text-sm text-brand-500 leading-relaxed">Full cost breakdown: grid cost + fabric modifier + control type + options + surcharges = dealer cost. Apply markup for sell price. Track margins per line and per quote.</p>
            </div>
        </div>
    </div>
</div>
@endsection
