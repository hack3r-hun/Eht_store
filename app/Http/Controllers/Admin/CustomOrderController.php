<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = CustomOrder::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.custom-orders.index', compact('orders'));
    }

    public function update(Request $request, CustomOrder $customOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(CustomOrder::STATUSES))],
            'quote_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customOrder->update($data);

        return back()->with('success', 'Custom order updated.');
    }

    public function destroy(CustomOrder $customOrder): RedirectResponse
    {
        if ($customOrder->reference_image) {
            Storage::disk('public')->delete($customOrder->reference_image);
        }

        $customOrder->delete();

        return back()->with('success', 'Custom order deleted.');
    }
}
