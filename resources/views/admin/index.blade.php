@extends('layouts.app')
@section('title', 'Admin Panel')

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="font-serif text-3xl">Admin Panel</h1>
        <p class="text-brand-400 text-sm mt-1">Manage suppliers, products, pricing grids, and business rules</p>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Suppliers</p>
            <p class="text-3xl font-serif mt-1">{{ $suppliers->count() }}</p>
            <a href="{{ route('admin.suppliers') }}" class="text-accent-500 hover:text-accent-600 text-sm mt-2 inline-block">Manage &rarr;</a>
        </div>
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Pricing Grid Cells</p>
            <p class="text-3xl font-serif mt-1">{{ number_format($totalGridCells) }}</p>
            <a href="{{ route('admin.products') }}" class="text-accent-500 hover:text-accent-600 text-sm mt-2 inline-block">View Products &rarr;</a>
        </div>
        <div class="stat-card card-elevated p-6">
            <p class="text-brand-400 text-xs font-mono uppercase tracking-wider">Business Rules</p>
            <p class="text-3xl font-serif mt-1">{{ $totalRules }}</p>
            <a href="{{ route('admin.rules') }}" class="text-accent-500 hover:text-accent-600 text-sm mt-2 inline-block">View Rules &rarr;</a>
        </div>
    </div>

    {{-- Supplier Overview --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Supplier Overview</h2>
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Supplier</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Products</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Fabrics</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Controls</th>
                        <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Surcharges</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($suppliers as $supplier)
                    <tr class="hover:bg-brand-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-semibold">{{ $supplier->name }}</p>
                            <p class="text-xs font-mono text-brand-400">{{ $supplier->code }}</p>
                        </td>
                        <td class="px-6 py-4 text-center font-mono text-sm">{{ $supplier->products_count }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm">{{ $supplier->fabrics_count }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm">{{ $supplier->control_types_count }}</td>
                        <td class="px-6 py-4 text-center font-mono text-sm">{{ $supplier->surcharges_count }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.suppliers.show', $supplier) }}" class="text-accent-500 hover:text-accent-600 text-sm font-medium">Details &rarr;</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="grid md:grid-cols-3 gap-4">
        <a href="{{ route('admin.suppliers') }}" class="card-elevated p-6 group hover:border-accent-400 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center group-hover:bg-accent-100 transition-colors">
                    <svg class="w-5 h-5 text-brand-500 group-hover:text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="font-semibold">Suppliers</h3>
            </div>
            <p class="text-sm text-brand-400">Manage supplier profiles, rounding rules, and default markup percentages</p>
        </a>
        <a href="{{ route('admin.products') }}" class="card-elevated p-6 group hover:border-accent-400 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center group-hover:bg-accent-100 transition-colors">
                    <svg class="w-5 h-5 text-brand-500 group-hover:text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </div>
                <h3 class="font-semibold">Products &amp; Pricing</h3>
            </div>
            <p class="text-sm text-brand-400">View product catalog and pricing grid matrices for each product</p>
        </a>
        <a href="{{ route('admin.rules') }}" class="card-elevated p-6 group hover:border-accent-400 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center group-hover:bg-accent-100 transition-colors">
                    <svg class="w-5 h-5 text-brand-500 group-hover:text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h3 class="font-semibold">Business Rules</h3>
            </div>
            <p class="text-sm text-brand-400">Compatibility rules, surcharge triggers, and rounding configurations</p>
        </a>
    </div>
</div>
@endsection
