<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BuyerDirectory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuyerController extends Controller
{
    public function __construct(protected BuyerDirectory $directory) {}

    public function index(Request $request): View
    {
        $all = $this->directory->all();
        $rows = $this->directory->filtered($request)->values();

        $perPage = in_array((int) $request->input('per_page', 20), [10, 20, 50, 100], true) ? (int) $request->input('per_page', 20) : 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $buyers = (new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));

        $summary = [
            'total' => $all->count(),
            'guests' => $all->reject(fn ($r) => $r->is_account)->count(),
            'accounts' => $all->filter(fn ($r) => $r->is_account)->count(),
            'email_ready' => $all->filter(fn ($r) => $r->opted_in && filled($r->email))->count(),
            'sms_ready' => $all->filter(fn ($r) => $r->opted_in && filled($r->phone))->count(),
        ];

        return view('admin.customers.buyers', compact('buyers', 'summary'));
    }

    /** CSV for email / SMS tools. Includes the opt-in column so the list can be filtered there too. */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->directory->filtered($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone (international)', 'Customer type', 'Orders', 'Total spent', 'First order', 'Last order', 'Agreed to offers']);

            foreach ($rows as $row) {
                fputcsv($out, array_map([$this, 'safeCell'], [
                    $row->name,
                    $row->email,
                    $row->phone ? '+'.$row->phone : '',
                    $row->is_account ? 'Account' : 'Guest',
                    $row->orders,
                    $row->spent,
                    $row->first_order_at->format('Y-m-d'),
                    $row->last_order_at->format('Y-m-d'),
                    $row->opted_in ? 'Yes' : 'No',
                ]));
            }

            fclose($out);
        }, 'customers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet apps from running text that starts like a formula (=, +, -, @). */
    public function safeCell(mixed $value): mixed
    {
        // A leading "+" is how phone numbers are written here, so only guard real formulas.
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
