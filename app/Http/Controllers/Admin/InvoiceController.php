<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceBuilder;
use App\Support\ImageProcessor;
use App\Support\InvoiceSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceBuilder $builder) {}

    public function edit(): View
    {
        return view('admin.invoice.edit', [
            's' => InvoiceSettings::all(),
            'toggles' => InvoiceSettings::TOGGLES,
            'hasLatestOrder' => Order::exists(),
            'defaults' => InvoiceSettings::defaults(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $defaults = InvoiceSettings::defaults();

        $rules = [
            'company_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:300'],
            'phone' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:160'],
            'website' => ['nullable', 'string', 'max:160'],
            'tax_label' => ['nullable', 'string', 'max:20'],
            'tax_number' => ['nullable', 'string', 'max:60'],
            'number_prefix' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9\-_\/]*$/'],
            'thank_you' => ['nullable', 'string', 'max:300'],
            'terms' => ['nullable', 'string', 'max:1500'],
            'bank_details' => ['nullable', 'string', 'max:800'],
            'footer_note' => ['nullable', 'string', 'max:200'],
            'copyright_holder' => ['required', 'string', 'max:120'],
            'copyright_text' => ['nullable', 'string', 'max:120'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'paper' => ['required', 'in:a4,letter'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        foreach (array_keys(InvoiceSettings::TOGGLES) as $toggle) {
            $rules[$toggle] = ['nullable', 'boolean'];
        }

        $data = $request->validate($rules);
        $current = InvoiceSettings::all();
        $values = [];

        foreach ($defaults as $key => $default) {
            if (is_bool($default)) {
                $values[$key] = $request->boolean($key);
            } elseif ($key === 'logo_path') {
                $values[$key] = $current['logo_path'];
            } else {
                $values[$key] = strip_tags((string) ($data[$key] ?? ''));
            }
        }

        if ($request->hasFile('logo')) {
            $this->discardLogo($current['logo_path']);
            $values['logo_path'] = ImageProcessor::store($request->file('logo'), 'invoice');
        } elseif ($request->boolean('remove_logo')) {
            $this->discardLogo($current['logo_path']);
            $values['logo_path'] = '';
        }

        InvoiceSettings::save($values);

        if ($request->boolean('preview')) {
            return redirect()->route('admin.invoice.preview');
        }

        return redirect()->route('admin.invoice.edit')->with('success', 'Invoice design saved. New and re-downloaded invoices use it straight away.');
    }

    public function reset(): RedirectResponse
    {
        $this->discardLogo(InvoiceSettings::all()['logo_path']);
        InvoiceSettings::save([]);

        return redirect()->route('admin.invoice.edit')->with('success', 'Invoice reset to the original design.');
    }

    /** Shows the invoice with the saved design — on the newest real order, or a sample one. */
    public function preview(Request $request): Response
    {
        $order = $request->get('source') === 'sample' || ! Order::exists()
            ? $this->builder->sampleOrder()
            : Order::with(['items', 'user'])->latest()->first();

        return $this->builder->build($order)->stream('invoice-preview.pdf');
    }

    protected function discardLogo(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            ImageProcessor::deleteWithThumb($path);
        }
    }
}
