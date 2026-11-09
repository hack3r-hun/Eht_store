@extends('layouts.admin')

@section('page-title', 'Customers')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$buyers->total()" count-label="people match" subtitle="Everyone who has ordered — guests and account holders. Matched across orders by email or phone.">
            <x-slot:action>
                <a href="{{ route('admin.customers.buyers.export', request()->query()) }}" class="btn-primary !py-2.5 !px-5 text-sm">⬇ Download list (CSV)</a>
            </x-slot:action>
        </x-admin-page-header>
    </x-reveal>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-5 border-b border-slate-200">
        <a href="{{ route('admin.customers.index') }}" class="px-4 py-2.5 text-sm font-semibold text-slate-500 hover:text-sage-700 border-b-2 border-transparent -mb-px">Registered accounts</a>
        <a href="{{ route('admin.customers.buyers') }}" class="px-4 py-2.5 text-sm font-semibold text-sage-700 border-b-2 border-sage-600 -mb-px">Everyone who ordered</a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 md:gap-4 mb-6">
        @foreach([
            ['People', $summary['total'], 'Everyone who ordered'],
            ['Guests', $summary['guests'], 'checked out without an account'],
            ['With accounts', $summary['accounts'], 'signed-in buyers'],
            ['Reachable by email', $summary['email_ready'], 'agreed to offers'],
            ['Reachable by SMS', $summary['sms_ready'], 'agreed to offers'],
        ] as [$label, $value, $note])
            <div class="admin-card admin-card-pad">
                <p class="text-xs text-slate-500 font-medium">{{ $label }}</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($value) }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $note }}</p>
            </div>
        @endforeach
    </div>

    <x-reveal type="fade-up" delay="40">
        <form method="GET" class="admin-filter-bar">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone" class="input-field admin-filter-search">
            <select name="type" class="input-field admin-filter-control">
                <option value="">Guests &amp; accounts</option>
                <option value="guest" @selected(request('type') === 'guest')>Guests only</option>
                <option value="account" @selected(request('type') === 'account')>Accounts only</option>
            </select>
            <select name="marketing" class="input-field admin-filter-control">
                <option value="">Any offers choice</option>
                <option value="opted" @selected(request('marketing') === 'opted')>Agreed to offers</option>
                <option value="not" @selected(request('marketing') === 'not')>Did not agree</option>
            </select>
            <select name="reach" class="input-field admin-filter-control">
                <option value="">Any contact detail</option>
                <option value="email" @selected(request('reach') === 'email')>Has email</option>
                <option value="sms" @selected(request('reach') === 'sms')>Has phone</option>
            </select>
            <select name="sort" class="input-field admin-filter-control">
                <option value="">Latest order</option>
                <option value="orders" @selected(request('sort') === 'orders')>Most orders</option>
                <option value="spent" @selected(request('sort') === 'spent')>Highest spend</option>
                <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
            </select>
            <div class="admin-filter-actions">
                <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                <a href="{{ route('admin.customers.buyers') }}" class="btn-outline !py-2.5 !px-5 text-sm">Reset</a>
            </div>
        </form>
    </x-reveal>

    <p class="text-xs text-slate-500 mb-3">Tip: the downloaded file respects the filters above — choose “Agreed to offers” before downloading to build an email or SMS list. People who did not tick the offers box at checkout should not be sent promotions.</p>

    <x-reveal type="fade-up" delay="80">
        <div class="admin-card overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr><th>Customer</th><th>Email</th><th>Phone</th><th>Type</th><th>Orders</th><th>Total spent</th><th>Last order</th><th>Offers</th></tr>
                </thead>
                <tbody>
                    @forelse($buyers as $buyer)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $buyer->name }}</td>
                            <td class="text-slate-600">{{ $buyer->email ?? '—' }}</td>
                            <td class="text-slate-500 whitespace-nowrap">{{ $buyer->phone ? '+'.$buyer->phone : '—' }}</td>
                            <td><span class="admin-badge {{ $buyer->is_account ? 'bg-sage-100 text-sage-700' : 'bg-terracotta-50 text-terracotta-600' }}">{{ $buyer->is_account ? 'Account' : 'Guest' }}</span></td>
                            <td>{{ $buyer->orders }}</td>
                            <td class="font-semibold text-slate-900">{{ shop_money($buyer->spent) }}</td>
                            <td class="text-slate-500 whitespace-nowrap">{{ $buyer->last_order_at->format('M d, Y') }}</td>
                            <td><span class="admin-badge {{ $buyer->opted_in ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $buyer->opted_in ? 'Yes' : 'No' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-16 text-slate-500">No one matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-reveal>

    <div class="mt-6">{{ $buyers->links() }}</div>
@endsection
