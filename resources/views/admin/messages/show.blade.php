@extends('layouts.admin')

@section('page-title', $message->subject)

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-[1fr_22rem] gap-6 items-start">
        <div class="space-y-6">
            <x-reveal type="fade-up">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                    <div class="flex flex-wrap justify-between gap-4 mb-6">
                        <div class="text-sm text-slate-500">
                            <p><strong>From:</strong> {{ $message->name }} ({{ $message->email }})</p>
                            <p><strong>Date:</strong> {{ $message->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <span class="admin-badge {{ $message->last_replied_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $message->last_replied_at ? 'Replied' : 'Needs reply' }}
                        </span>
                    </div>
                    <div class="prose prose-slate max-w-none">{{ nl2br(e($message->message)) }}</div>
                </div>
            </x-reveal>

            <x-reveal type="fade-up" delay="80">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-6">
                    <h2 class="font-bold text-slate-900 mb-4">Reply History</h2>
                    @if($message->replies->isEmpty())
                        <p class="text-sm text-slate-500">No replies sent yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($message->replies->sortBy('created_at') as $reply)
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="flex flex-wrap justify-between gap-2 text-xs text-slate-500 mb-2">
                                        <span>By {{ $reply->admin?->name ?? 'Admin' }}</span>
                                        <span>{{ $reply->created_at->format('M d, Y H:i') }} @if($reply->sent_at) - emailed @else - saved, email failed @endif</span>
                                    </div>
                                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $reply->message }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-reveal>
        </div>

        <x-reveal type="fade-left" delay="100">
            <aside class="bg-white rounded-2xl border border-slate-100 shadow-card p-6 sticky top-24">
                <h2 class="font-bold text-slate-900 mb-2">Send Reply</h2>
                <p class="text-sm text-slate-500 mb-4">Reply will be emailed to {{ $message->email }} and saved here.</p>

                <form action="{{ route('admin.messages.reply', $message) }}" method="POST" class="space-y-4">
                    @csrf
                    <textarea name="message" rows="8" class="input-field" required placeholder="Write your reply...">{{ old('message') }}</textarea>
                    @error('message')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                    <button type="submit" class="btn-primary w-full justify-center">Send Reply</button>
                </form>

                <div class="border-t border-slate-100 mt-6 pt-6">
                    <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Delete message and replies?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 text-sm font-medium hover:underline">Delete Conversation</button>
                    </form>
                </div>
            </aside>
        </x-reveal>
    </div>
@endsection
