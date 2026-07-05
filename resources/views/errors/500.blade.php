@extends('layouts.storefront')

@section('title', 'Server Error')

@section('content')
    <section class="max-w-lg mx-auto px-4 py-24 text-center">
        <div class="text-8xl font-bold text-red-200 mb-4">500</div>
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Something Went Wrong</h1>
        <p class="text-slate-600 mb-8">We're working to fix this. Please try again later.</p>
        <a href="{{ route('home') }}" class="btn-primary">Go Home</a>
    </section>
@endsection
