@extends('layouts.admin')

@section('page-title', 'Subscribers')

@section('content')
    <x-admin-page-header :count="$subscribers->total()" count-label="newsletter subscribers" subtitle="Emails collected from the newsletter popup, plus open back-in-stock requests." />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Email</th><th>Subscribed</th></tr></thead>
                <tbody>
                    @forelse($subscribers as $subscriber)
                        <tr><td>{{ $subscriber->email }}</td><td class="text-slate-500">{{ $subscriber->created_at->format('d M Y') }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-500 py-10">No subscribers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $subscribers->links() }}</div>
        </div>

        <div class="admin-card overflow-x-auto self-start">
            <div class="px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Waiting for restock</h2></div>
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Email</th></tr></thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr><td>{{ $alert->product?->name }}</td><td class="text-slate-500">{{ $alert->email }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-slate-500 py-10">No open stock alerts.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
