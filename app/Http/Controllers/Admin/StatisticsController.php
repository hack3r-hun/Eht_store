<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends Controller
{
    public function __construct(protected StatisticsService $statistics) {}

    public function index(Request $request): View
    {
        [$start, $end, $period] = $this->statistics->resolveRange($request);

        // The page runs a couple dozen queries; a short cache keyed by the exact range keeps
        // switching tabs/filters snappy while staying fresh enough for day-to-day use.
        $data = Cache::remember(
            'admin.statistics.v2.'.md5($start->toDateTimeString().'|'.$end->toDateTimeString()),
            60,
            fn () => $this->statistics->build($start, $end)
        );

        return view('admin.statistics.index', [
            'data' => $data,
            'start' => $start,
            'end' => $end,
            'period' => $period,
            'presets' => StatisticsService::PRESETS,
        ]);
    }

    /** CSV of every order in the selected range, for spreadsheets / the accountant. */
    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->statistics->resolveRange($request);
        $filename = 'orders-'.$start->format('Y-m-d').'-to-'.$end->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Date', 'Status', 'Payment method', 'Payment status', 'City', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Gift wrap', 'Total']);

            Order::whereBetween('created_at', [$start, $end])->orderBy('created_at')->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $order) {
                    fputcsv($out, array_map([$this, 'safeCell'], [
                        $order->order_number,
                        $order->created_at->format('Y-m-d H:i'),
                        $order->status->label(),
                        $order->payment_method->label(),
                        $order->payment_status->label(),
                        $order->shipping_address['city'] ?? '',
                        $order->subtotal,
                        $order->discount,
                        $order->shipping,
                        $order->tax,
                        $order->gift_wrap_fee,
                        $order->total,
                    ]));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet apps from executing text that starts like a formula (=, +, -, @). */
    public function safeCell(mixed $value): mixed
    {
        return is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }
}
