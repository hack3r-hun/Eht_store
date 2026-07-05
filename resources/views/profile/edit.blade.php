@extends('layouts.storefront')

@section('title', 'My Account')

@section('content')
    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="section-title mb-10 animate-fade-in-up">My Account</h1>

        <div class="space-y-6 animate-fade-in-up">
            <div class="flex flex-wrap gap-3 mb-2">
                <a href="{{ route('account.orders.index') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">My Orders →</a>
                <a href="{{ route('account.addresses') }}" class="text-sm font-medium text-brand-700 hover:text-brand-800">My Addresses →</a>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 sm:p-8">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 sm:p-8">
                @include('profile.partials.update-password-form')
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 sm:p-8">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>
@endsection
