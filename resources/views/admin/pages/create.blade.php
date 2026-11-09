@extends('layouts.admin')

@section('page-title', 'New page')

@section('content')
    <form action="{{ route('admin.pages.store') }}" method="POST" class="max-w-xl space-y-6">
        @csrf
        <div class="admin-card admin-card-pad space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Page title</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="input-field" placeholder="e.g. Wholesale, Gift Guide, Care Tips">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Web address</label>
                <div class="flex items-center rounded-xl border border-slate-200 bg-white overflow-hidden focus-within:border-sage-500">
                    <span class="px-3 text-sm text-slate-400 bg-slate-50 self-stretch flex items-center">{{ url('/p') }}/</span>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="flex-1 border-0 focus:ring-0 py-3" placeholder="made-from-title">
                </div>
                <p class="text-xs text-slate-400 mt-1">Letters, numbers and dashes. Leave empty to build it from the title.</p>
                @error('slug')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="btn-primary">Create page</button>
            <a href="{{ route('admin.pages.index') }}" class="btn-outline">Cancel</a>
        </div>
    </form>
@endsection
