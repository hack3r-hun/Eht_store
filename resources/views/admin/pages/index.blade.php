@extends('layouts.admin')

@section('page-title', 'CMS Pages')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$pages->count()" count-label="pages" subtitle="Edit storefront CMS content" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <div class="admin-card divide-y divide-slate-100">
            @foreach($pages as $page)
                <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50">
                    <div>
                        <p class="font-semibold">{{ $page->title }}</p>
                        <p class="text-sm text-slate-500">/{{ $page->slug }}</p>
                    </div>
                    <a href="{{ route('admin.pages.edit', $page) }}" class="admin-action-link">Edit</a>
                </div>
            @endforeach
        </div>
    </x-reveal>
@endsection
