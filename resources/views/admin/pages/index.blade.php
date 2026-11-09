@extends('layouts.admin')

@section('page-title', 'CMS Pages')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$pages->count()" count-label="pages" subtitle="Edit text, images and the layout of every storefront page — or create new pages.">
            <x-slot:action>
                <a href="{{ route('admin.pages.create') }}" class="btn-primary !py-2.5 !px-5 text-sm">+ New page</a>
            </x-slot:action>
        </x-admin-page-header>
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <div class="admin-card divide-y divide-slate-100">
            @foreach($pages as $page)
                @php
                    $url = $page->isSystem() ? url(\App\Support\Cms\PageDefaults::SYSTEM[$page->slug]['url']) : route('pages.show', $page->slug);
                @endphp
                <div class="px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 flex flex-wrap items-center gap-2">
                            {{ $page->title }}
                            @if($page->isSystem())
                                <span class="admin-badge bg-slate-100 text-slate-600">Built-in</span>
                            @else
                                <span class="admin-badge {{ $page->is_published ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $page->is_published ? 'Live' : 'Draft' }}</span>
                            @endif
                            @if(is_array($page->sections))
                                <span class="admin-badge bg-sage-100 text-sage-700">Custom layout</span>
                            @endif
                        </p>
                        <p class="text-sm text-slate-500 truncate">{{ parse_url($url, PHP_URL_PATH) ?: '/' }}</p>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-sm text-slate-500 hover:text-sage-700">View</a>
                        <a href="{{ route('admin.pages.edit', $page) }}" class="admin-action-link">Edit</a>
                        @unless($page->isSystem())
                            <x-confirm-delete :action="route('admin.pages.destroy', $page)" title="Delete page?" item="{{ $page->title }}" message="This page will be permanently removed." confirm-label="Delete" class="inline-block">
                                <span class="sr-only">Delete</span>
                            </x-confirm-delete>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </x-reveal>
@endsection
