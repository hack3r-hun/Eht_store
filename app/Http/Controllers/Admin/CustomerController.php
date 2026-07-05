<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::role('customer')
            ->withCount('orders')
            ->withSum('orders as orders_total', 'total')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('verified'), fn ($query) => match ($request->verified) {
                'yes' => $query->whereNotNull('email_verified_at'),
                'no' => $query->whereNull('email_verified_at'),
                default => $query,
            })
            ->when($request->filled('orders'), fn ($query) => match ($request->orders) {
                'with' => $query->has('orders'),
                'without' => $query->doesntHave('orders'),
                default => $query,
            })
            ->when($request->filled('joined_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('joined_from')))
            ->when($request->filled('joined_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('joined_to')))
            ->when($request->get('sort') === 'orders', fn ($query) => $query->orderByDesc('orders_count'))
            ->when($request->get('sort') === 'spent', fn ($query) => $query->orderByDesc('orders_total'))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
    }
}
