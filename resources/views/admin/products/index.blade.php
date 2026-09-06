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
        <form method="GET" class="admin-filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or SKU" class="input-field admin-filter-search">
            <select name="category" class="input-field admin-filter-control">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input-field admin-filter-control">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="stock" class="input-field admin-filter-control">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <select name="per_page" class="input-field admin-filter-control" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="admin-filter-actions">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.products.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
                <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="admin-action-link">Active</a>
                <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="admin-action-link">Inactive</a>
                <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="admin-action-link">Low stock</a>
            </div>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card admin-card-pad" x-data="{ selected: [] }">
            {{-- Keep bulk form OUTSIDE the table so per-row Archive forms are not nested. --}}
            <form id="bulk-products-delete" method="POST" action="{{ route('admin.products.bulk-destroy') }}">
                @csrf
                @method('DELETE')
            </form>

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

            <div class="hidden md:block overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input
                                    type="checkbox"
                                    class="rounded text-sage-600"
                                    @change="selected = $event.target.checked ? [...new Set(Array.from(document.querySelectorAll('[data-product-checkbox]')).map((checkbox) => checkbox.value))] : []"
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
                                        form="bulk-products-delete"
                                        name="product_ids[]"
                                        value="{{ $product->id }}"
                                        class="rounded text-sage-600"
                                        x-model="selected"
                                    >
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $product->image_url }}" alt="" class="w-10 h-10 rounded-xl object-cover border border-slate-100 group-hover:scale-110 transition-transform duration-300">
                                        <span class="font-semibold text-slate-900 group-hover:text-sage-700 transition-colors">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-500 font-mono text-xs">{{ $product->sku }}</td>
                                <td class="font-semibold text-slate-900">
                                    @if($product->active_size_count > 1)
                                        <span class="block">From {{ shop_money($product->from_price) }}</span>
                                        <span class="text-xs font-normal text-slate-500">{{ $product->active_size_count }} sizes</span>
                                    @else
                                        {{ shop_money($product->from_price) }}
                                        @if($product->active_size_count === 1)
                                            <span class="block text-xs font-normal text-slate-500">{{ $product->activeVariants->first()?->size }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-badge {{ $product->stock_quantity <= $product->low_stock_threshold ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
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
                                        <a href="{{ route('admin.products.edit', $product) }}" class="admin-action-link">Edit →</a>
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
                                    No products found. <a href="{{ route('admin.products.create') }}" class="text-sage-600 font-semibold hover:underline">Add a product</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list — same $products collection, shares the `selected` bulk-select state above --}}
            <div class="md:hidden space-y-3">
                @forelse($products as $product)
                    <div class="rounded-2xl border border-slate-100 p-4">
                        <div class="flex items-start gap-3 mb-3">
                            <input
                                data-product-checkbox
                                type="checkbox"
                                form="bulk-products-delete"
                                name="product_ids[]"
                                value="{{ $product->id }}"
                                class="rounded text-sage-600 mt-1"
                                x-model="selected"
                            >
                            <img src="{{ $product->image_url }}" alt="" class="w-12 h-12 rounded-xl object-cover border border-slate-100 shrink-0">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900 truncate">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ $product->sku }}</p>
                            </div>
                            <span class="admin-badge shrink-0 {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm mb-4">
                            <span class="font-semibold text-slate-900">
                                @if($product->active_size_count > 1)
                                    From {{ shop_money($product->from_price) }} <span class="text-xs font-normal text-slate-500">({{ $product->active_size_count }} sizes)</span>
                                @else
                                    {{ shop_money($product->from_price) }}
                                @endif
                            </span>
                            <span class="admin-badge {{ $product->stock_quantity <= $product->low_stock_threshold ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $product->stock_quantity }} units
                            </span>
                        </div>
                        <div class="flex items-center justify-end gap-4 pt-3 border-t border-slate-100">
                            <a href="{{ route('admin.products.edit', $product) }}" class="admin-action-link">Edit →</a>
                            <x-confirm-delete
                                :action="route('admin.products.destroy', $product)"
                                title="Archive this product?"
                                :item="$product->name"
                                message="It will be hidden from the website and normal admin lists, but kept in the database."
                                confirm-label="Archive"
                            />
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16 text-slate-500">
                        <x-icon name="cube" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                        No products found. <a href="{{ route('admin.products.create') }}" class="text-sage-600 font-semibold hover:underline">Add a product</a>
                    </div>
                @endforelse
            </div>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
