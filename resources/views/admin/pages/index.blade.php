@extends('layouts.admin')

@section('page-title', 'CMS Pages')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-100 shadow-card divide-y divide-slate-100">
        @foreach($pages as $page)
            <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50">
                <div>
                    <p class="font-semibold">{{ $page->title }}</p>
                    <p class="text-sm text-slate-500">/{{ $page->slug }}</p>
                </div>
                <a href="{{ route('admin.pages.edit', $page) }}" class="text-brand-700 font-medium hover:underline">Edit</a>
            </div>
        @endforeach
    </div>
@endsection
