@extends('layouts.storefront')

@section('title', 'Page Not Found')

@section('content')
    <section class="max-w-lg mx-auto px-4 py-24 text-center animate-fade-in-up">
        <div class="text-8xl font-bold text-brand-200 mb-4">404</div>
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Page Not Found</h1>
        <p class="text-slate-600 mb-8">Sorry, we couldn't find the page you're looking for.</p>
        <a href="{{ route('home') }}" class="btn-primary">Go Home</a>
    </section>
@endsection
