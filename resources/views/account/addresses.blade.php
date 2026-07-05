@extends('layouts.storefront')

@section('title', 'My Addresses')

@section('content')
    <section class="max-w-3xl mx-auto px-4 py-12">
        <h1 class="section-title mb-8">Saved Addresses</h1>

        <div class="space-y-4 mb-8">
            @forelse($addresses as $address)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 card-hover flex justify-between">
                    <div>
                        <p class="font-semibold">{{ $address->label }} @if($address->is_default)<span class="text-xs bg-brand-100 text-brand-700 px-2 py-0.5 rounded ml-2">Default</span>@endif</p>
                        <p class="text-sm text-slate-600 mt-1">{{ $address->full_name }} · {{ $address->phone }}</p>
                        <p class="text-sm text-slate-500">{{ $address->address_line }}, {{ $address->city }}</p>
                    </div>
                    <form action="{{ route('account.addresses.destroy', $address) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 text-sm hover:underline">Remove</button>
                    </form>
                </div>
            @empty
                <p class="text-slate-500">No saved addresses yet.</p>
            @endforelse
        </div>

        <form action="{{ route('account.addresses.store') }}" method="POST" class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 space-y-4">
            @csrf
            <h2 class="font-semibold text-lg">Add New Address</h2>
            <div class="grid grid-cols-2 gap-4">
                <input type="text" name="label" placeholder="Label (Home, Office)" class="input-field" required>
                <input type="text" name="full_name" placeholder="Full Name" class="input-field" required>
            </div>
            <input type="text" name="phone" placeholder="Phone" class="input-field" required>
            <input type="text" name="address_line" placeholder="Address" class="input-field" required>
            <div class="grid grid-cols-2 gap-4">
                <input type="text" name="city" placeholder="City" class="input-field" required>
                <input type="text" name="postal_code" placeholder="Postal Code" class="input-field">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" class="rounded text-brand-600"> Set as default</label>
            <button type="submit" class="btn-primary">Save Address</button>
        </form>
    </section>
@endsection
