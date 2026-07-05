@extends('layouts.admin')

@section('page-title', 'Customers')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$customers->total()" count-label="registered customers" subtitle="Search, filter, and sort customers by account and order activity" />
    </x-reveal>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-card mb-6 grid grid-cols-1 md:grid-cols-4 xl:grid-cols-7 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone" class="input-field md:col-span-2">
            <select name="verified" class="input-field">
                <option value="">Any verification</option>
                <option value="yes" @selected(request('verified') === 'yes')>Verified</option>
                <option value="no" @selected(request('verified') === 'no')>Unverified</option>
            </select>
            <select name="orders" class="input-field">
                <option value="">Any orders</option>
                <option value="with" @selected(request('orders') === 'with')>Has orders</option>
                <option value="without" @selected(request('orders') === 'without')>No orders</option>
            </select>
            <select name="sort" class="input-field">
                <option value="">Newest first</option>
                <option value="orders" @selected(request('sort') === 'orders')>Most orders</option>
                <option value="spent" @selected(request('sort') === 'spent')>Highest spend</option>
            </select>
            <select name="per_page" class="input-field" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm flex-1">Filter</button>
                <a href="{{ route('admin.customers.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
            <input type="date" name="joined_from" value="{{ request('joined_from') }}" class="input-field">
            <input type="date" name="joined_to" value="{{ request('joined_to') }}" class="input-field">
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card">
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr class="group">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white text-sm font-bold group-hover:scale-110 transition-transform duration-300">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-600">{{ $customer->email }}</td>
                                <td class="text-slate-500">{{ $customer->phone ?? '-' }}</td>
                                <td>
                                    <span class="admin-badge bg-indigo-100 text-indigo-700">{{ $customer->orders_count }} orders</span>
                                </td>
                                <td class="font-semibold text-slate-900">{{ shop_money($customer->orders_total ?? 0) }}</td>
                                <td>
                                    <span class="admin-badge {{ $customer->email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td class="text-slate-500">{{ $customer->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-16 text-slate-500">
                                    <x-icon name="users" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                                    No customers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
