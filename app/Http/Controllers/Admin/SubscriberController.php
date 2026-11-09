<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\StockAlert;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function index(): View
    {
        return view('admin.subscribers.index', [
            'subscribers' => NewsletterSubscriber::latest()->paginate(30),
            'alerts' => StockAlert::with('product')->whereNull('notified_at')->latest()->limit(50)->get(),
        ]);
    }
}
