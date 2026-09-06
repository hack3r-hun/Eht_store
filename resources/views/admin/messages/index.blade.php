@extends('layouts.admin')

@section('page-title', 'Messages')

@section('content')
    <x-reveal type="fade-up">
        <x-admin-page-header :count="$messages->total()" count-label="messages" subtitle="Contact form submissions and customer reply status" />
    </x-reveal>

    <div class="grid grid-cols-1 xl:grid-cols-[18rem_1fr] gap-6">
        <x-reveal type="fade-right">
            <aside class="admin-card p-0 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900">Customers</h2>
                    <p class="text-xs text-slate-500 mt-1">Filter conversations by email</p>
                </div>
                <div class="max-h-[34rem] overflow-y-auto divide-y divide-slate-50">
                    <a href="{{ route('admin.messages.index', request()->except('customer', 'page')) }}"
                       class="block px-5 py-3 text-sm font-semibold {{ request('customer') ? 'text-slate-600 hover:bg-slate-50' : 'text-sage-700 bg-sage-50' }}">
                        All customers
                    </a>
                    @foreach($customers as $customer)
                        <a href="{{ route('admin.messages.index', array_merge(request()->except('page'), ['customer' => $customer->email])) }}"
                           class="block px-5 py-3 text-sm transition-colors {{ request('customer') === $customer->email ? 'bg-sage-50 text-sage-700' : 'hover:bg-slate-50 text-slate-600' }}">
                            <span class="block font-semibold truncate">{{ $customer->name }}</span>
                            <span class="block text-xs truncate">{{ $customer->email }}</span>
                            <span class="block text-[11px] text-slate-400 mt-0.5">{{ $customer->messages_count }} messages</span>
                        </a>
                    @endforeach
                </div>
            </aside>
        </x-reveal>

        <div>
            <x-reveal type="fade-up" delay="40">
                <form method="GET" class="admin-filter-bar">
                    @if(request('customer'))
                        <input type="hidden" name="customer" value="{{ request('customer') }}">
                    @endif
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, or subject" class="input-field admin-filter-search">
                    <select name="status" class="input-field admin-filter-control">
                        <option value="">Any status</option>
                        <option value="unread" @selected(request('status') === 'unread')>Unread</option>
                        <option value="unreplied" @selected(request('status') === 'unreplied')>Needs reply</option>
                        <option value="replied" @selected(request('status') === 'replied')>Replied</option>
                    </select>
                    <div class="admin-filter-actions">
                        <button type="submit" class="btn-primary !py-2.5 !px-5 text-sm">Filter</button>
                        <a href="{{ route('admin.messages.index') }}" class="btn-outline !py-2.5 !px-5 text-sm text-center">Reset</a>
                    </div>
                </form>
            </x-reveal>

            <x-reveal type="fade-up" delay="80">
                <div class="admin-card divide-y divide-slate-50">
                    @forelse($messages as $message)
                        <a href="{{ route('admin.messages.show', $message) }}"
                           class="block px-6 py-5 transition-all duration-300 hover:bg-sage-50/50 group {{ !$message->is_read ? 'bg-sage-50/30 border-l-4 border-sage-500' : 'hover:pl-8' }}">
                            <div class="flex justify-between items-start gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        @if(!$message->is_read)
                                            <span class="w-2 h-2 rounded-full bg-sage-500 animate-pulse-soft"></span>
                                        @endif
                                        <p class="font-semibold text-slate-900 group-hover:text-sage-700 transition-colors truncate">{{ $message->subject }}</p>
                                        @if($message->last_replied_at)
                                            <span class="admin-badge bg-green-100 text-green-700">Replied</span>
                                        @else
                                            <span class="admin-badge bg-amber-100 text-amber-700">Needs reply</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-slate-500">{{ $message->name }} - {{ $message->email }}</p>
                                    <p class="text-sm text-slate-400 mt-1 line-clamp-1">{{ \Illuminate\Support\Str::limit($message->message, 100) }}</p>
                                </div>
                                <div class="shrink-0 text-right text-xs text-slate-400 group-hover:text-sage-600 transition-colors">
                                    <p>{{ $message->created_at->diffForHumans() }}</p>
                                    <p class="mt-1">{{ $message->replies_count }} replies</p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-16 text-center text-slate-500">
                            <x-icon name="mail" class="w-14 h-14 mx-auto mb-3 text-slate-300" />
                            <p>No messages found.</p>
                        </div>
                    @endforelse
                </div>
            </x-reveal>

            <div class="mt-6">{{ $messages->links() }}</div>
        </div>
    </div>
@endsection
