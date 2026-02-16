@extends('layouts.app')
@section('title', 'Quotes')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-serif text-3xl">Quotes</h1>
            <p class="text-brand-400 text-sm mt-1">Manage multi-line quotes with automatic margin calculation</p>
        </div>
        <form action="{{ route('quotes.store') }}" method="POST" x-data="{ open: false }">
            @csrf
            <button type="button" @click="open = !open" class="bg-brand-900 hover:bg-brand-800 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                + New Quote
            </button>
            <div x-show="open" x-transition @click.away="open = false" class="absolute right-6 mt-2 w-80 card-elevated p-4 z-10 space-y-3">
                <input name="project_name" placeholder="Project Name" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                <input name="customer_name" placeholder="Customer Name" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                <input name="customer_email" placeholder="Email" class="w-full border border-brand-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-accent-500">
                <button type="submit" class="w-full bg-accent-500 hover:bg-accent-600 text-white font-medium px-4 py-2 rounded-lg text-sm transition-colors">Create Quote</button>
            </div>
        </form>
    </div>

    @if($quotes->count())
    <div class="card-elevated overflow-hidden">
        <table class="w-full">
            <thead class="bg-brand-50 border-b border-brand-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Quote #</th>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Project</th>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Customer</th>
                    <th class="text-center px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Lines</th>
                    <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Total Sell</th>
                    <th class="text-right px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Margin</th>
                    <th class="text-left px-6 py-3 text-xs font-mono text-brand-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-100">
                @foreach($quotes as $quote)
                <tr class="hover:bg-brand-50/50 transition-colors">
                    <td class="px-6 py-4 font-mono text-sm">{{ $quote->quote_number }}</td>
                    <td class="px-6 py-4 text-sm">{{ $quote->project_name ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $quote->customer_name ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-center">{{ $quote->line_items_count }}</td>
                    <td class="px-6 py-4 text-sm text-right font-mono">${{ number_format($quote->total_sell, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right font-mono text-emerald-600">${{ number_format($quote->total_margin, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                            {{ $quote->status === 'draft' ? 'bg-brand-100 text-brand-600' : '' }}
                            {{ $quote->status === 'sent' ? 'bg-blue-100 text-blue-600' : '' }}
                            {{ $quote->status === 'accepted' ? 'bg-emerald-100 text-emerald-600' : '' }}">
                            {{ ucfirst($quote->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('quotes.show', $quote) }}" class="text-accent-500 hover:text-accent-600 text-sm font-medium">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $quotes->links() }}
    @else
    <div class="card-elevated p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="text-brand-400 mt-4 text-lg">No quotes yet</p>
        <p class="text-brand-300 text-sm mt-1">Create your first quote to start building line items</p>
    </div>
    @endif
</div>
@endsection
