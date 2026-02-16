@extends('layouts.app')
@section('title', $quote->quote_number)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <a href="{{ route('quotes.index') }}" class="text-brand-400 hover:text-brand-600 text-sm">&larr; All Quotes</a>
            <h1 class="font-serif text-3xl mt-1">{{ $quote->quote_number }}</h1>
            <div class="flex gap-4 mt-1 text-sm text-brand-400">
                @if($quote->project_name)<span>{{ $quote->project_name }}</span>@endif
                @if($quote->customer_name)<span>&middot; {{ $quote->customer_name }}</span>@endif
                <span>&middot; {{ ucfirst($quote->status) }}</span>
            </div>
        </div>
        <div class="text-right">
            <p class="text-xs font-mono text-brand-400 uppercase">Total Sell</p>
            <p class="font-serif text-3xl">${{ number_format($quote->total_sell, 2) }}</p>
            <p class="text-sm text-emerald-600 font-mono">Margin: ${{ number_format($quote->total_margin, 2) }}</p>
        </div>
    </div>

    {{-- Line Items --}}
    @if($quote->lineItems->count())
    <div class="card-elevated overflow-hidden">
        <table class="w-full">
            <thead class="bg-brand-50 border-b border-brand-200">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-mono text-brand-400 uppercase">Product</th>
                    <th class="text-left px-4 py-3 text-xs font-mono text-brand-400 uppercase">Fabric</th>
                    <th class="text-left px-4 py-3 text-xs font-mono text-brand-400 uppercase">Control</th>
                    <th class="text-center px-4 py-3 text-xs font-mono text-brand-400 uppercase">Size</th>
                    <th class="text-center px-4 py-3 text-xs font-mono text-brand-400 uppercase">Qty</th>
                    <th class="text-right px-4 py-3 text-xs font-mono text-brand-400 uppercase">Cost</th>
                    <th class="text-right px-4 py-3 text-xs font-mono text-brand-400 uppercase">Sell</th>
                    <th class="text-right px-4 py-3 text-xs font-mono text-brand-400 uppercase">Margin</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-100">
                @foreach($quote->lineItems as $item)
                <tr class="hover:bg-brand-50/50">
                    <td class="px-4 py-3">
                        <p class="text-sm font-medium">{{ $item->product->name }}</p>
                        <p class="text-xs text-brand-400">{{ $item->product->supplier->code }}</p>
                        @if($item->room_name)<p class="text-xs text-accent-600">{{ $item->room_name }}{{ $item->window_name ? " — {$item->window_name}" : '' }}</p>@endif
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $item->fabric->name }}</td>
                    <td class="px-4 py-3 text-sm">{{ $item->controlType->name }}</td>
                    <td class="px-4 py-3 text-sm text-center font-mono">
                        {{ $item->width }}&Prime;&times;{{ $item->height }}&Prime;
                        @if($item->width != $item->rounded_width || $item->height != $item->rounded_height)
                        <br><span class="text-xs text-brand-300">&rarr; {{ $item->rounded_width }}&Prime;&times;{{ $item->rounded_height }}&Prime;</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-center">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono">${{ number_format($item->line_cost, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono font-semibold">${{ number_format($item->line_sell, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono text-emerald-600">${{ number_format($item->line_margin, 2) }}</td>
                    <td class="px-4 py-3">
                        <form action="{{ route('quotes.remove-line', [$quote, $item]) }}" method="POST" onsubmit="return confirm('Remove this line?')">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-brand-50 border-t-2 border-brand-200">
                <tr>
                    <td colspan="5" class="px-4 py-3 text-sm font-semibold">Totals</td>
                    <td class="px-4 py-3 text-sm text-right font-mono font-semibold">${{ number_format($quote->total_cost, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono font-semibold">${{ number_format($quote->total_sell, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-emerald-600">${{ number_format($quote->total_margin, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Add Line Item --}}
    <div class="card-elevated p-6" x-data="quoteConfigurator()">
        <h2 class="font-semibold text-lg mb-4">Add Line Item</h2>
        <form action="{{ route('quotes.add-line', $quote) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Supplier</label>
                    <select x-model="supplierId" @change="loadSupplierData()" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                        <option value="">Select...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Product</label>
                    <select name="product_id" x-model="productId" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                        <option value="">Select...</option>
                        <template x-for="p in products" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Fabric</label>
                    <select name="fabric_id" x-model="fabricId" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                        <option value="">Select...</option>
                        <template x-for="f in fabrics" :key="f.id">
                            <option :value="f.id" x-text="`${f.name} (${f.price_group})`"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Control Type</label>
                    <select name="control_type_id" x-model="controlTypeId" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                        <option value="">Select...</option>
                        <template x-for="c in controls" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Width</label>
                    <input name="width" type="number" step="0.125" required class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500" placeholder="36">
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Height</label>
                    <input name="height" type="number" step="0.125" required class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500" placeholder="48">
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Qty</label>
                    <input name="quantity" type="number" value="1" min="1" required class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Markup %</label>
                    <input name="markup_pct" type="number" value="55" step="0.5" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Room</label>
                    <input name="room_name" type="text" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500" placeholder="Living Room">
                </div>
                <div>
                    <label class="block text-xs font-medium text-brand-500 mb-1">Window</label>
                    <input name="window_name" type="text" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500" placeholder="East Wall">
                </div>
            </div>
            <button type="submit" class="bg-accent-500 hover:bg-accent-600 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
                Add to Quote
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function quoteConfigurator() {
    return {
        supplierId: '', productId: '', fabricId: '', controlTypeId: '',
        products: [], fabrics: [], controls: [],
        async loadSupplierData() {
            if (!this.supplierId) return;
            const [products, fabrics, controls] = await Promise.all([
                fetch(`/api/products/${this.supplierId}`).then(r => r.json()),
                fetch(`/api/fabrics/${this.supplierId}`).then(r => r.json()),
                fetch(`/api/controls/${this.supplierId}`).then(r => r.json()),
            ]);
            this.products = products;
            this.fabrics = fabrics;
            this.controls = controls;
            this.productId = '';
            this.fabricId = '';
            this.controlTypeId = '';
        }
    }
}
</script>
@endpush
@endsection
