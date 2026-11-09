@extends('layouts.admin')

@section('page-title', 'Customers')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$customers->total()" count-label="registered customers" subtitle="Search customers and sort by order activity" />
    </x-reveal>

    <div class="flex gap-2 mb-5 border-b border-slate-200">
        <a href="{{ route('admin.customers.index') }}" class="px-4 py-2.5 text-sm font-semibold text-sage-700 border-b-2 border-sage-600 -mb-px">Registered accounts</a>
        <a href="{{ route('admin.customers.buyers') }}" class="px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-sage-700 border-b-2 border-transparent -mb-px">Everyone who ordered <span class="text-xs font-normal">(guests too)</span></a>
    </div>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone" class="input-field admin-filter-search">
            <select name="orders" class="input-field admin-filter-control">
                <option value="">Any orders</option>
                <option value="with" @selected(request('orders') === 'with')>Has orders</option>
                <option value="without" @selected(request('orders') === 'without')>No orders</option>
            </select>
            <select name="sort" class="input-field admin-filter-control">
                <option value="">Newest first</option>
                <option value="orders" @selected(request('sort') === 'orders')>Most orders</option>
                <option value="spent" @selected(request('sort') === 'spent')>Highest spend</option>
            </select>
            <select name="per_page" class="input-field admin-filter-control" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }} / page</option>
                @endforeach
            </select>
            <div class="admin-filter-actions">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.customers.index') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
        </form>
    </x-reveal>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card hidden md:block">
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
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-sage-500 to-sage-700 flex items-center justify-center text-white text-sm font-bold group-hover:scale-110 transition-transform duration-300">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-600">{{ $customer->email }}</td>
                                <td class="text-slate-500">{{ $customer->phone ?? '—' }}</td>
                                <td>
                                    <span class="admin-badge bg-sage-100 text-sage-700">{{ $customer->orders_count }} {{ Str::plural('order', $customer->orders_count) }}</span>
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

    {{-- Mobile card list — same $customers collection --}}
    <div class="md:hidden space-y-3">
        @forelse($customers as $customer)
            <div class="admin-card admin-card-pad">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-sage-500 to-sage-700 flex items-center justify-center text-white text-sm font-bold shrink-0">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900 truncate">{{ $customer->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $customer->email }}</p>
                    </div>
                    <span class="admin-badge shrink-0 {{ $customer->email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $customer->email_verified_at ? 'Verified' : 'Unverified' }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm pt-3 border-t border-slate-100">
                    <span class="admin-badge bg-sage-100 text-sage-700">{{ $customer->orders_count }} {{ Str::plural('order', $customer->orders_count) }}</span>
                    <span class="font-semibold text-slate-900">{{ shop_money($customer->orders_total ?? 0) }}</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">{{ $customer->phone ?? 'No phone' }} · Joined {{ $customer->created_at->format('M d, Y') }}</p>
            </div>
        @empty
            <div class="admin-card admin-card-pad text-center py-16 text-slate-500">
                <x-icon name="users" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                No customers found.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
