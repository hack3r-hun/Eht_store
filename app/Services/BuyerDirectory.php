<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Everyone who has placed an order, as one row per person — guests and account holders alike.
 * A person is identified by email, or by phone when no email was given. Their name, phone and
 * "send me offers" choice are taken from their most recent order, so a later opt-out wins.
 */
class BuyerDirectory
{
    /** @return Collection<int, object> */
    public function all(): Collection
    {
        $orders = Order::query()
            ->where(fn ($q) => $q->whereNotNull('contact_email')->orWhereNotNull('contact_phone'))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'status', 'total', 'created_at', 'contact_name', 'contact_email', 'contact_phone', 'marketing_opt_in']);

        return $orders
            ->groupBy(fn (Order $o) => $o->contact_email ?: $o->contact_phone)
            ->map(function (Collection $group, string $key) {
                $latest = $group->last();
                $valid = $group->filter(fn (Order $o) => $o->status !== OrderStatus::Cancelled);

                return (object) [
                    'key' => $key,
                    'name' => $group->reverse()->pluck('contact_name')->filter()->first() ?? '—',
                    'email' => $group->reverse()->pluck('contact_email')->filter()->first(),
                    'phone' => $group->reverse()->pluck('contact_phone')->filter()->first(),
                    'orders' => $valid->count(),
                    'spent' => (float) $valid->sum('total'),
                    'first_order_at' => $group->first()->created_at,
                    'last_order_at' => $latest->created_at,
                    'is_account' => $group->contains(fn (Order $o) => $o->user_id !== null),
                    'opted_in' => (bool) $latest->marketing_opt_in,
                ];
            })
            ->values();
    }

    /** Applies the filters/sort shared by the page and the CSV export. */
    public function filtered(Request $request): Collection
    {
        $rows = $this->all();

        if ($request->filled('search')) {
            $needle = mb_strtolower($request->string('search')->toString());
            // Phones are stored in international digits (92300…); let people search as they'd type
            // a number: 0300…, +92 300… or just the last digits.
            $digits = preg_replace('/\D+/', '', $needle);
            $digits = preg_match('/^(00)?92/', $digits) ? preg_replace('/^(00)?92/', '', $digits) : ltrim($digits, '0');

            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower((string) $r->name), $needle)
                || str_contains((string) $r->email, $needle)
                || ($digits !== '' && str_contains((string) $r->phone, $digits)));
        }

        $rows = match ($request->get('type')) {
            'guest' => $rows->reject(fn ($r) => $r->is_account),
            'account' => $rows->filter(fn ($r) => $r->is_account),
            default => $rows,
        };

        $rows = match ($request->get('marketing')) {
            'opted' => $rows->filter(fn ($r) => $r->opted_in),
            'not' => $rows->reject(fn ($r) => $r->opted_in),
            default => $rows,
        };

        $rows = match ($request->get('reach')) {
            'email' => $rows->filter(fn ($r) => filled($r->email)),
            'sms' => $rows->filter(fn ($r) => filled($r->phone)),
            default => $rows,
        };

        return match ($request->get('sort')) {
            'orders' => $rows->sortByDesc('orders'),
            'spent' => $rows->sortByDesc('spent'),
            'name' => $rows->sortBy(fn ($r) => mb_strtolower((string) $r->name)),
            default => $rows->sortByDesc(fn ($r) => $r->last_order_at->timestamp),
        };
    }
}
