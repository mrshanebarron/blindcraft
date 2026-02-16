@extends('layouts.app')
@section('title', 'Business Rules')

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ route('admin.index') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; Admin Panel</a>
        <h1 class="font-serif text-3xl mt-1">Business Rules</h1>
        <p class="text-brand-400 text-sm mt-1">Compatibility constraints, surcharge triggers, and dimension rounding configurations</p>
    </div>

    {{-- Compatibility Rules --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Compatibility Rules ({{ $compatibility->count() }})</h2>
        @if($compatibility->count())
        <div class="space-y-3">
            @foreach($compatibility as $rule)
            <div class="card-elevated p-5 breakdown-row">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex px-2 py-0.5 text-xs font-mono font-semibold rounded
                                {{ $rule->rule_type === 'requires' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $rule->rule_type === 'excludes' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $rule->rule_type === 'max_size_with' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $rule->rule_type === 'min_size_with' ? 'bg-purple-100 text-purple-700' : '' }}">
                                {{ strtoupper(str_replace('_', ' ', $rule->rule_type)) }}
                            </span>
                            @if($rule->product)
                            <span class="text-sm text-brand-500">{{ $rule->product->name }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-brand-600 mt-1">{{ $rule->message }}</p>
                        <div class="flex gap-4 mt-2 text-xs text-brand-400">
                            <span>Subject: <span class="font-mono">{{ $rule->subject_type }}:{{ $rule->subject_id }}</span></span>
                            @if($rule->target_type)
                            <span>Target: <span class="font-mono">{{ $rule->target_type }}:{{ $rule->target_id }}</span></span>
                            @endif
                            @if($rule->target_value)
                            <span>Value: <span class="font-mono">{{ $rule->target_value }}</span></span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right text-xs text-brand-400">
                        <p>{{ $rule->supplier->name }}</p>
                        <p class="font-mono">{{ $rule->supplier->code }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="card-elevated p-8 text-center text-brand-400">No compatibility rules defined</div>
        @endif
    </div>

    {{-- Surcharges --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Surcharges ({{ $surcharges->count() }})</h2>
        @if($surcharges->count())
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Supplier</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Trigger</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Condition</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Charge</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($surcharges as $surcharge)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 text-sm">{{ $surcharge->supplier->name }}</td>
                        <td class="px-6 py-3 text-sm">{{ $surcharge->product ? $surcharge->product->name : 'All' }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex px-2 py-0.5 text-xs font-mono font-semibold rounded
                                {{ $surcharge->trigger_type === 'oversize' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ strtoupper($surcharge->trigger_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm font-mono text-brand-500">
                            {{ $surcharge->trigger_dimension }} &gt; {{ $surcharge->trigger_value }}&Prime;
                        </td>
                        <td class="px-6 py-3 text-sm text-right font-mono font-semibold">
                            @if($surcharge->charge_type === 'flat')
                                ${{ number_format($surcharge->charge_amount, 2) }} flat
                            @elseif($surcharge->charge_type === 'percentage')
                                {{ $surcharge->charge_amount }}%
                            @elseif($surcharge->charge_type === 'per_sqft')
                                ${{ number_format($surcharge->charge_amount, 2) }}/sqft
                            @elseif($surcharge->charge_type === 'per_united_inch')
                                ${{ number_format($surcharge->charge_amount, 2) }}/ui
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="card-elevated p-8 text-center text-brand-400">No surcharges defined</div>
        @endif
    </div>

    {{-- Rounding Rules --}}
    <div>
        <h2 class="font-serif text-2xl mb-4">Rounding Rules ({{ $rounding->count() }})</h2>
        @if($rounding->count())
        <div class="card-elevated overflow-hidden">
            <table class="w-full">
                <thead class="bg-brand-50 border-b border-brand-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Supplier</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Dimension</th>
                        <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase">Method</th>
                        <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase">Increment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100">
                    @foreach($rounding as $rule)
                    <tr class="hover:bg-brand-50/50">
                        <td class="px-6 py-3 text-sm">{{ $rule->supplier->name }}</td>
                        <td class="px-6 py-3 text-sm">{{ $rule->product ? $rule->product->name : 'All Products' }}</td>
                        <td class="px-6 py-3 text-sm font-mono">{{ $rule->dimension }}</td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-flex px-2 py-0.5 text-xs font-mono rounded bg-brand-100 text-brand-700">
                                {{ str_replace('_', ' ', $rule->method) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-right font-mono font-semibold">{{ $rule->increment }}&Prime;</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="card-elevated p-8 text-center text-brand-400">No rounding rules defined</div>
        @endif
    </div>

    {{-- Rule Engine Info --}}
    <div class="card-elevated p-6">
        <h2 class="font-semibold text-lg mb-3">How Rules Are Applied</h2>
        <div class="grid md:grid-cols-3 gap-6 text-sm text-brand-500">
            <div>
                <h3 class="font-semibold text-brand-700 mb-1">1. Rounding</h3>
                <p>Customer dimensions are rounded to the nearest supplier-defined increment before grid lookup. Product-specific rules override supplier defaults.</p>
            </div>
            <div>
                <h3 class="font-semibold text-brand-700 mb-1">2. Compatibility</h3>
                <p>Configuration is validated against rules. Invalid combos (e.g., motor on unsupported product, oversize motorized) are rejected with messages.</p>
            </div>
            <div>
                <h3 class="font-semibold text-brand-700 mb-1">3. Surcharges</h3>
                <p>After base pricing, surcharges are applied for oversized or undersized orders. Charges can be flat, percentage, per-sqft, or per-united-inch.</p>
            </div>
        </div>
    </div>
</div>
@endsection
