@extends('layouts.admin')

@section('page-title', 'Categories')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$categories->total()" count-label="categories">
            <x-slot:action>
                <a href="{{ route('admin.categories.create') }}" class="btn-primary !py-2.5 !px-5 text-sm">+ Add Category</a>
            </x-slot:action>
        </x-admin-page-header>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                            <x-icon name="folder" class="w-5 h-5" />
                                        </span>
                                        <span class="font-semibold text-slate-900 group-hover:text-brand-700 transition-colors">{{ $category->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-500">{{ $category->parent?->name ?? '—' }}</td>
                                <td>
                                    <span class="admin-badge bg-brand-50 text-brand-700">{{ $category->products_count }} items</span>
                                </td>
                                <td>
                                    <span class="admin-badge {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="admin-action-link">Edit →</a>
                                    <x-confirm-delete
                                        :action="route('admin.categories.destroy', $category)"
                                        title="Delete this category?"
                                        :item="$category->name"
                                        message="It will be permanently deleted along with its associations. This action cannot be undone."
                                    />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-16 text-slate-500">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $categories->links() }}</div>
@endsection
