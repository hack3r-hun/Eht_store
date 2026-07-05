@extends('layouts.admin')

@section('page-title', $category->exists ? 'Edit Category' : 'Add Category')

@section('content')
    <form action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="max-w-xl space-y-6">
        @csrf
        @if($category->exists) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="input-field" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Parent Category</label>
                <select name="parent_id" class="input-field">
                    <option value="">None (top level)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-admin.image-uploader
                name="image"
                :multiple="false"
                :aspect-ratio="4/3"
                label="Category Image"
                hint="Choose an image (max 5MB). Crop it, then click Save."
                existing-hint="Current category image. Upload a new one to replace it after save."
            >
                @if($category->image)
                    <x-slot:existingImages>
                        <div class="relative">
                            <img src="{{ $category->image_url }}" alt="" class="w-32 h-24 object-cover rounded-xl border border-slate-100">
                            <span class="absolute -top-2 -left-2 text-[10px] font-bold bg-brand-600 text-white px-1.5 py-0.5 rounded">Current</span>
                        </div>
                    </x-slot:existingImages>
                @endif
                @error('image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </x-admin.image-uploader>
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" class="input-field">{{ old('description', $category->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="input-field" min="0">
            </div>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true)) class="rounded text-brand-600"> Active</label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Save</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
@endsection
