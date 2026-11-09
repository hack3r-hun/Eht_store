<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = Review::with('product')
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $review->update(['is_approved' => $request->boolean('is_approved')]);

        return back()->with('success', $review->is_approved ? 'Review approved.' : 'Review hidden.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
