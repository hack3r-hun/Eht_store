@extends('layouts.admin')

@section('page-title', 'Products')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$products->total()" count-label="products">
            <x-slot:action>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.products.archived') }}" class="btn-outline !py-2.5 !px-5 text-sm">
                        Archived ({{ $archivedCount }})
                    </a>
                    <a href="{{ route('admin.products.create') }}" class="btn-primary !py-2.5 !px-5 text-sm">+ Add Product</a>
                </div>
            </x-slot:action>
        </x-admin-page-header>
    </x-reveal>

    <x-reveal type="fade-up" delay="60">
        <form method="GET" class="admin-card mb-6 grid grid-cols-1 md:grid-cols-6 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU" class="input-field md:col-span-2">
            <select name="category" class="input-field">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input-field">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="stock" class="input-field">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <select name="per_page" class="input-field" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="md:col-span-6 flex flex-wrap gap-2">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.products.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
                <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="admin-action-link">Active</a>
                <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="admin-action-link">Inactive</a>
                <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="admin-action-link">Low stock</a>
            </div>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card" x-data="{ selected: [] }">
            <form id="bulk-products-delete" method="POST" action="{{ route('admin.products.bulk-destroy') }}">
                @csrf
                @method('DELETE')
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-900" x-text="selected.length">0</span> selected
                    </p>
                    <x-confirm-delete
                        action="#"
                        form-id="bulk-products-delete"
                        title="Archive selected products?"
                        message="Selected products will disappear from the website and admin product list, but their database records and images will be kept."
                        confirm-label="Archive Selected"
                        x-show="selected.length > 0"
                        x-cloak
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input
                                        type="checkbox"
                                        class="rounded text-brand-600"
                                        @change="selected = $event.target.checked ? Array.from(document.querySelectorAll('[data-product-checkbox]')).map((checkbox) => checkbox.value) : []"
                                        :checked="selected.length === {{ $products->count() }} && selected.length > 0"
                                    >
                                </th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr class="group">
                                    <td>
                                        <input
                                            data-product-checkbox
                                            type="checkbox"
                                            name="product_ids[]"
                                            value="{{ $product->id }}"
                                            class="rounded text-brand-600"
                                            x-model="selected"
                                        >
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $product->image_url }}" alt="" class="w-10 h-10 rounded-xl object-cover border border-slate-100 group-hover:scale-110 transition-transform duration-300">
                                            <span class="font-semibold text-slate-900 group-hover:text-brand-700 transition-colors">{{ $product->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-slate-500 font-mono text-xs">{{ $product->sku }}</td>
                                    <td class="font-semibold text-slate-900">{{ shop_money($product->effective_price) }}</td>
                                    <td>
                                        <span class="admin-badge {{ $product->stock_quantity <= $product->low_stock_threshold ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $product->stock_quantity }} units
                                        </span>
                                    </td>
                                    <td>
                                        <span class="admin-badge {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.products.edit', $product) }}" class="admin-action-link">Edit -></a>
                                            <x-confirm-delete
                                                :action="route('admin.products.destroy', $product)"
                                                title="Archive this product?"
                                                :item="$product->name"
                                                message="It will be hidden from the website and normal admin lists, but kept in the database."
                                                confirm-label="Archive"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-16 text-slate-500">
                                        <x-icon name="cube" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                                        No products found. <a href="{{ route('admin.products.create') }}" class="text-brand-600 font-semibold hover:underline">Add a product</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
