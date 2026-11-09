@extends('layouts.admin')

@section('page-title', 'Custom Orders')

@section('content')
    <x-admin-page-header :count="$orders->total()" count-label="requests" subtitle="Custom piece requests from the storefront — send a quote and track progress." />

    <form method="GET" class="admin-filter-bar">
        <select name="status" class="input-field admin-filter-control" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(\App\Models\CustomOrder::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="space-y-4">
        @forelse($orders as $order)
            <div class="admin-card admin-card-pad">
                <div class="flex flex-col lg:flex-row gap-6">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <h3 class="font-bold text-slate-900">{{ $order->item_type }}</h3>
                            <span class="admin-badge bg-slate-100 text-slate-700">{{ \App\Models\CustomOrder::STATUSES[$order->status] ?? $order->status }}</span>
                        </div>
                        <p class="text-sm text-slate-600 whitespace-pre-line mb-3">{{ $order->description }}</p>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-500">
                            <div><dt class="inline font-semibold">Colours:</dt> {{ $order->colors ?: '—' }}</div>
                            <div><dt class="inline font-semibold">Size:</dt> {{ $order->size ?: '—' }}</div>
                            <div><dt class="inline font-semibold">Budget:</dt> {{ $order->budget ?: '—' }}</div>
                            <div><dt class="inline font-semibold">Needed by:</dt> {{ $order->needed_by?->format('d M Y') ?? '—' }}</div>
                        </dl>
                        <p class="text-xs text-slate-400 mt-3">
                            {{ $order->name }} · <a class="text-sage-700 underline" href="mailto:{{ $order->email }}">{{ $order->email }}</a>@if($order->phone) · {{ $order->phone }}@endif · {{ $order->created_at->diffForHumans() }}
                        </p>
                        @if($order->reference_image_url)
                            <a href="{{ $order->reference_image_url }}" target="_blank" rel="noopener" class="inline-block mt-3">
                                <img src="{{ $order->reference_image_url }}" alt="Reference" class="h-24 w-24 rounded-xl object-cover border border-slate-200">
                            </a>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.custom-orders.update', $order) }}" class="lg:w-72 space-y-3 shrink-0">
                        @csrf @method('PATCH')
                        <select name="status" class="input-field">
                            @foreach(\App\Models\CustomOrder::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" name="quote_amount" value="{{ $order->quote_amount }}" placeholder="Quote amount" class="input-field">
                        <textarea name="admin_notes" rows="2" placeholder="Internal notes" class="input-field">{{ $order->admin_notes }}</textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary !py-2 !px-4 text-sm flex-1">Save</button>
                        </div>
                    </form>
                </div>
                <div class="mt-3 flex justify-end">
                    <x-confirm-delete :action="route('admin.custom-orders.destroy', $order)" title="Delete request?" item="{{ $order->item_type }}" message="This custom order request will be permanently removed." confirm-label="Delete" class="inline-block">
                        <span class="sr-only">Delete</span>
                    </x-confirm-delete>
                </div>
            </div>
        @empty
            <div class="admin-card p-16 text-center text-slate-500">No custom order requests yet.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
