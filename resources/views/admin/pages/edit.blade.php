@extends('layouts.admin')

@section('page-title', 'Edit: '.$page->title)

@section('content')
    <form action="{{ route('admin.pages.update', $page) }}" method="POST" class="max-w-3xl space-y-6">
        @csrf @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Title</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" class="input-field" required>
            </div>

            @if($page->slug === 'home')
                <div>
                    <label class="block text-sm font-medium mb-2">Hero Title</label>
                    <input type="text" name="meta[hero_title]" value="{{ old('meta.hero_title', $page->meta['hero_title'] ?? '') }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Hero Subtitle</label>
                    <input type="text" name="meta[hero_subtitle]" value="{{ old('meta.hero_subtitle', $page->meta['hero_subtitle'] ?? '') }}" class="input-field">
                </div>
            @elseif($page->slug === 'about')
                <div>
                    <label class="block text-sm font-medium mb-2">Hero Title</label>
                    <input type="text" name="meta[hero_title]" value="{{ old('meta.hero_title', $page->meta['hero_title'] ?? '') }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Content (HTML allowed)</label>
                    <textarea name="content" rows="10" class="input-field font-mono text-sm">{{ old('content', $page->content) }}</textarea>
                </div>
            @else
                <div>
                    <label class="block text-sm font-medium mb-2">Content</label>
                    <textarea name="content" rows="10" class="input-field">{{ old('content', $page->content) }}</textarea>
                </div>
            @endif
        </div>

        <button type="submit" class="btn-primary">Save Page</button>
    </form>
@endsection
