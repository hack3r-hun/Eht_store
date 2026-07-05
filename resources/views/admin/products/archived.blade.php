@extends('layouts.admin')

@section('page-title', 'Archived Products')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$products->total()" count-label="archived products">
            <x-slot:action>
                <a href="{{ route('admin.products.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Back to Products</a>
            </x-slot:action>
        </x-admin-page-header>
    </x-reveal>

    <x-reveal type="fade-up" delay="60">
        <form method="GET" class="admin-card mb-6 flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search archived name or SKU" class="input-field flex-1">
            <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Search</button>
            <a href="{{ route('admin.products.archived') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Archived</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $product->image_url }}" alt="" class="w-10 h-10 rounded-xl object-cover border border-slate-100">
                                        <span class="font-semibold text-slate-900">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-500 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="font-semibold text-slate-900">{{ shop_money($product->effective_price) }}</td>
                                <td class="text-slate-500 text-sm">{{ $product->deleted_at?->diffForHumans() }}</td>
                                <td class="text-right">
                                    <form action="{{ route('admin.products.restore', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="admin-action-link">Restore</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-16 text-slate-500">
                                    <x-icon name="inbox" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                                    No archived products.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
