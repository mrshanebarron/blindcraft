@extends('layouts.app')
@section('title', 'Product Configurator')

@section('content')
<div x-data="configurator()" x-cloak class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-3xl">Product Configurator</h1>
            <p class="text-brand-400 text-sm mt-1">Configure a window covering and see real-time pricing with full cost breakdown</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Configuration Panel --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Step 1: Supplier --}}
            <div class="card-elevated p-6">
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 bg-brand-900 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                    <h2 class="font-semibold">Select Supplier</h2>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($suppliers as $supplier)
                    <button @click="selectSupplier({{ $supplier->id }}, '{{ $supplier->name }}', '{{ $supplier->code }}', {{ $supplier->default_markup_pct }})"
                            :class="supplierId == {{ $supplier->id }} ? 'border-accent-500 bg-accent-500/5 ring-1 ring-accent-500/20' : 'border-brand-200 hover:border-brand-300'"
                            class="p-4 border rounded-lg text-left transition-all">
                        <p class="font-semibold">{{ $supplier->name }}</p>
                        <p class="font-mono text-xs text-brand-400">{{ $supplier->code }} &middot; {{ $supplier->rounding_method }} rounding</p>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Step 2: Product --}}
            <div class="card-elevated p-6" x-show="supplierId" x-transition>
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 bg-brand-900 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                    <h2 class="font-semibold">Select Product</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="product in products" :key="product.id">
                        <button @click="selectProduct(product)"
                                :class="productId == product.id ? 'border-accent-500 bg-accent-500/5 ring-1 ring-accent-500/20' : 'border-brand-200 hover:border-brand-300'"
                                class="p-4 border rounded-lg text-left transition-all">
                            <p class="font-semibold" x-text="product.name"></p>
                            <p class="text-xs text-brand-400 mt-0.5" x-text="product.category.replace(/_/g, ' ')"></p>
                            <p class="text-xs text-brand-400 mt-1 font-mono" x-text="`${product.min_width}″–${product.max_width}″ W × ${product.min_height}″–${product.max_height}″ H`"></p>
                            <p class="text-xs text-brand-500 mt-2 line-clamp-2" x-text="product.description"></p>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step 3: Fabric --}}
            <div class="card-elevated p-6" x-show="productId" x-transition>
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 bg-brand-900 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                    <h2 class="font-semibold">Select Fabric</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="fabric in fabrics" :key="fabric.id">
                        <button @click="fabricId = fabric.id; fabricGroup = fabric.price_group; calculate()"
                                :class="fabricId == fabric.id ? 'border-accent-500 bg-accent-500/5 ring-1 ring-accent-500/20' : 'border-brand-200 hover:border-brand-300'"
                                class="p-3 border rounded-lg text-left transition-all">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full border border-brand-200 flex-shrink-0" :style="`background-color: ${fabric.color_hex || '#ccc'}`"></div>
                                <span class="font-medium text-sm" x-text="fabric.name"></span>
                            </div>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-xs font-mono px-1.5 py-0.5 bg-brand-100 rounded" x-text="'Group ' + fabric.price_group"></span>
                                <span class="text-xs text-brand-400" x-text="fabric.opacity.replace(/_/g, ' ')"></span>
                            </div>
                            <p x-show="fabric.price_modifier > 0" class="text-xs text-accent-600 mt-1" x-text="'+$' + fabric.price_modifier + ' modifier'"></p>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step 4: Control Type --}}
            <div class="card-elevated p-6" x-show="fabricId" x-transition>
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 bg-brand-900 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                    <h2 class="font-semibold">Control Type</h2>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="ctrl in controls" :key="ctrl.id">
                        <button @click="controlTypeId = ctrl.id; calculate()"
                                :class="controlTypeId == ctrl.id ? 'border-accent-500 bg-accent-500/5 ring-1 ring-accent-500/20' : 'border-brand-200 hover:border-brand-300'"
                                class="p-3 border rounded-lg text-left transition-all">
                            <p class="font-medium text-sm" x-text="ctrl.name"></p>
                            <p class="text-xs text-brand-400 mt-1">
                                <span x-show="ctrl.price_adder > 0" x-text="'+$' + ctrl.price_adder"></span>
                                <span x-show="ctrl.price_multiplier > 1" x-text="'×' + ctrl.price_multiplier"></span>
                                <span x-show="ctrl.price_adder == 0 && ctrl.price_multiplier == 1">Included</span>
                            </p>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step 5: Dimensions + Options --}}
            <div class="card-elevated p-6" x-show="controlTypeId" x-transition>
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-6 h-6 bg-brand-900 text-white rounded-full flex items-center justify-center text-xs font-bold">5</span>
                    <h2 class="font-semibold">Dimensions &amp; Options</h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-brand-500 mb-1">Width (inches)</label>
                        <input type="number" x-model="width" @input.debounce.300ms="calculate()" step="0.125" min="1"
                               class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 outline-none"
                               :placeholder="`${selectedProduct?.min_width || 12}–${selectedProduct?.max_width || 96}`">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-brand-500 mb-1">Height (inches)</label>
                        <input type="number" x-model="height" @input.debounce.300ms="calculate()" step="0.125" min="1"
                               class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 outline-none"
                               :placeholder="`${selectedProduct?.min_height || 12}–${selectedProduct?.max_height || 120}`">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-brand-500 mb-1">Quantity</label>
                        <input type="number" x-model="quantity" @input.debounce.300ms="calculate()" min="1" max="100"
                               class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-brand-500 mb-1">Markup %</label>
                        <input type="number" x-model="markupPct" @input.debounce.300ms="calculate()" min="0" max="500" step="0.5"
                               class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-accent-500/20 focus:border-accent-500 outline-none">
                    </div>
                </div>

                {{-- Options --}}
                <div x-show="options.length > 0" class="mt-4">
                    <p class="text-xs font-medium text-brand-500 mb-2">Add-on Options</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="opt in options" :key="opt.id">
                            <label :class="selectedOptions.includes(opt.id) ? 'bg-accent-500/10 border-accent-500 text-accent-700' : 'border-brand-200 hover:border-brand-300'"
                                   class="inline-flex items-center gap-2 border rounded-lg px-3 py-2 text-sm cursor-pointer transition-all">
                                <input type="checkbox" :value="opt.id" x-model.number="selectedOptions" @change="calculate()" class="rounded border-brand-300 text-accent-500 focus:ring-accent-500/20">
                                <span x-text="opt.name"></span>
                                <span class="text-xs text-brand-400 font-mono" x-text="opt.price_adder > 0 ? '+$' + opt.price_adder : '+' + opt.price_pct + '%'"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pricing Panel (Sticky) --}}
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-4">
                {{-- Live Price --}}
                <div class="card-elevated p-6" x-show="result" x-transition>
                    <div x-show="result?.success">
                        <p class="text-xs font-mono text-brand-400 uppercase tracking-wider mb-1">Sell Price</p>
                        <p class="font-serif text-4xl text-brand-900" x-text="'$' + (result?.pricing?.line_sell || 0).toLocaleString('en-US', {minimumFractionDigits: 2})"></p>
                        <p class="text-sm text-brand-400 mt-1">
                            <span x-text="result?.pricing?.quantity"></span> unit<span x-show="result?.pricing?.quantity > 1">s</span>
                            @ <span x-text="'$' + (result?.pricing?.unit_sell || 0).toFixed(2)"></span> each
                        </p>

                        <div class="mt-4 pt-4 border-t border-brand-100 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-brand-400">Dealer Cost</span>
                                <span class="font-mono" x-text="'$' + (result?.pricing?.unit_cost || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-brand-400">Markup</span>
                                <span class="font-mono" x-text="(result?.pricing?.markup_pct || 0) + '%'"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-brand-400">Unit Sell</span>
                                <span class="font-mono font-semibold" x-text="'$' + (result?.pricing?.unit_sell || 0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-brand-100">
                                <span class="text-brand-400">Margin</span>
                                <span class="font-mono text-emerald-600" x-text="'$' + (result?.pricing?.line_margin || 0).toFixed(2) + ' (' + (result?.pricing?.margin_pct || 0) + '%)'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Errors --}}
                    <div x-show="result && !result.success">
                        <p class="text-xs font-mono text-red-400 uppercase tracking-wider mb-2">Configuration Error</p>
                        <template x-for="err in (result?.breakdown?.errors || [])" :key="err">
                            <p class="text-sm text-red-600 flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span x-text="err"></span>
                            </p>
                        </template>
                    </div>
                </div>

                {{-- Calculation Breakdown --}}
                <div class="card-elevated p-6" x-show="result?.success" x-transition>
                    <p class="text-xs font-mono text-brand-400 uppercase tracking-wider mb-3">Calculation Trace</p>
                    <div class="space-y-1 text-xs font-mono">
                        <template x-for="step in (result?.breakdown?.steps || [])" :key="step.step">
                            <div class="breakdown-row py-1.5 px-2 rounded">
                                <template x-if="step.step === 'rounding'">
                                    <div>
                                        <span class="text-brand-400">round</span>
                                        <span x-text="`${step.original.width}″×${step.original.height}″`"></span>
                                        <span class="text-brand-300">&rarr;</span>
                                        <span class="text-accent-600" x-text="`${step.rounded.width}″×${step.rounded.height}″`"></span>
                                        <span class="text-brand-300" x-text="`(${step.method})`"></span>
                                    </div>
                                </template>
                                <template x-if="step.step === 'grid_lookup'">
                                    <div>
                                        <span class="text-brand-400">grid</span>
                                        <span x-text="`Group ${step.price_group}`"></span>
                                        <span class="text-brand-300">=</span>
                                        <span class="text-emerald-600" x-text="`$${step.dealer_cost}`"></span>
                                    </div>
                                </template>
                                <template x-if="step.step === 'fabric_modifier'">
                                    <div x-show="step.modifier != 0">
                                        <span class="text-brand-400">fabric</span>
                                        <span x-text="`+$${step.modifier}`"></span>
                                        <span class="text-brand-300">=</span>
                                        <span x-text="`$${step.subtotal}`"></span>
                                    </div>
                                </template>
                                <template x-if="step.step === 'control_type'">
                                    <div>
                                        <span class="text-brand-400">ctrl</span>
                                        <span x-text="step.name"></span>
                                        <span x-show="step.multiplier != 1" x-text="`×${step.multiplier}`"></span>
                                        <span x-show="step.adder > 0" x-text="`+$${step.adder}`"></span>
                                        <span class="text-brand-300">=</span>
                                        <span x-text="`$${step.subtotal.toFixed(2)}`"></span>
                                    </div>
                                </template>
                                <template x-if="step.step === 'options' && step.options.length > 0">
                                    <div>
                                        <span class="text-brand-400">opts</span>
                                        <span x-text="`+$${step.total_options}`"></span>
                                        <span class="text-brand-300">=</span>
                                        <span x-text="`$${step.subtotal}`"></span>
                                    </div>
                                </template>
                                <template x-if="step.step === 'surcharges' && step.applied.length > 0">
                                    <div>
                                        <span class="text-red-400">surcharge</span>
                                        <template x-for="s in step.applied" :key="s.name">
                                            <span x-text="`${s.name} +$${s.amount}`" class="text-red-500"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="step.step === 'markup'">
                                    <div class="pt-1 border-t border-brand-100">
                                        <span class="text-brand-400">cost</span>
                                        <span x-text="`$${step.unit_cost}`"></span>
                                        <span class="text-brand-300">×</span>
                                        <span x-text="`${step.markup_pct}%`"></span>
                                        <span class="text-brand-300">=</span>
                                        <span class="text-emerald-600 font-semibold" x-text="`$${step.unit_sell}`"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Empty State --}}
                <div class="card-elevated p-8 text-center" x-show="!result">
                    <svg class="w-12 h-12 mx-auto text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <p class="text-brand-400 mt-3 text-sm">Configure a product to see live pricing</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function configurator() {
    return {
        supplierId: null,
        supplierName: '',
        supplierCode: '',
        productId: null,
        selectedProduct: null,
        fabricId: null,
        fabricGroup: null,
        controlTypeId: null,
        width: '',
        height: '',
        quantity: 1,
        markupPct: 55,
        selectedOptions: [],
        products: [],
        fabrics: [],
        controls: [],
        options: [],
        result: null,
        loading: false,

        async selectSupplier(id, name, code, defaultMarkup) {
            this.supplierId = id;
            this.supplierName = name;
            this.supplierCode = code;
            this.markupPct = defaultMarkup;
            this.productId = null;
            this.selectedProduct = null;
            this.fabricId = null;
            this.controlTypeId = null;
            this.selectedOptions = [];
            this.result = null;

            const [products, fabrics, controls, options] = await Promise.all([
                fetch(`/api/products/${id}`).then(r => r.json()),
                fetch(`/api/fabrics/${id}`).then(r => r.json()),
                fetch(`/api/controls/${id}`).then(r => r.json()),
                fetch(`/api/options/${id}`).then(r => r.json()),
            ]);
            this.products = products;
            this.fabrics = fabrics;
            this.controls = controls;
            this.options = options;
        },

        selectProduct(product) {
            this.productId = product.id;
            this.selectedProduct = product;
            this.fabricId = null;
            this.controlTypeId = null;
            this.result = null;
        },

        async calculate() {
            if (!this.productId || !this.fabricId || !this.controlTypeId || !this.width || !this.height) return;

            this.loading = true;
            try {
                const res = await fetch('/api/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        fabric_id: this.fabricId,
                        control_type_id: this.controlTypeId,
                        width: parseFloat(this.width),
                        height: parseFloat(this.height),
                        quantity: parseInt(this.quantity) || 1,
                        markup_pct: parseFloat(this.markupPct),
                        option_ids: this.selectedOptions,
                    }),
                });
                this.result = await res.json();
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        }
    }
}
</script>
@endpush
@endsection
