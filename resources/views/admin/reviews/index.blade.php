@extends('layouts.admin')

@section('page-title', 'Reviews')

@section('content')
    <x-admin-page-header :count="$reviews->total()" count-label="reviews" subtitle="Approve or hide customer reviews. Verified buyers are published automatically." />

    <form method="GET" class="admin-filter-bar">
        <select name="status" class="input-field admin-filter-control" onchange="this.form.submit()">
            <option value="">All reviews</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending approval</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
        </select>
    </form>

    <div class="admin-card divide-y divide-slate-50">
        @forelse($reviews as $review)
            <div class="p-5 flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="text-amber-500 tracking-widest" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', $review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        <span class="font-semibold text-slate-900">{{ $review->title ?: 'Review' }}</span>
                        @if($review->is_verified)<span class="admin-badge bg-green-100 text-green-700">Verified buyer</span>@endif
                        <span class="admin-badge {{ $review->is_approved ? 'bg-sage-100 text-sage-700' : 'bg-amber-100 text-amber-700' }}">{{ $review->is_approved ? 'Published' : 'Pending' }}</span>
                    </div>
                    <p class="text-sm text-slate-600 whitespace-pre-line">{{ $review->body }}</p>
                    <p class="text-xs text-slate-400 mt-2">{{ $review->name }} · {{ $review->product?->name }} · {{ $review->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="is_approved" value="{{ $review->is_approved ? 0 : 1 }}">
                        <button type="submit" class="btn-outline !py-2 !px-4 text-sm">{{ $review->is_approved ? 'Hide' : 'Approve' }}</button>
                    </form>
                    <x-confirm-delete :action="route('admin.reviews.destroy', $review)" title="Delete review?" item="{{ $review->name }}" message="This review will be permanently removed." confirm-label="Delete" class="inline-block">
                        <span class="sr-only">Delete</span>
                    </x-confirm-delete>
                </div>
            </div>
        @empty
            <div class="p-16 text-center text-slate-500">No reviews yet.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $reviews->links() }}</div>
@endsection
