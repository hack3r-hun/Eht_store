@extends('layouts.admin')

@section('page-title', 'Invoice')

@section('content')
    @php
        $field = fn ($name) => old($name, $s[$name] ?? '');
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500 max-w-2xl">Design the PDF invoice customers and your team download. Company details, what is shown, wording and branding are all controlled here — changes apply to every invoice immediately, including old orders.</p>
            @unless(module_enabled('invoice'))
                <p class="mt-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 inline-block">Customer invoice downloads are switched <strong>off</strong> in <a class="underline" href="{{ route('admin.modules.index') }}">Modules</a>. Admins can still download invoices.</p>
            @endunless
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.invoice.preview', ['source' => 'sample']) }}" target="_blank" rel="noopener" class="btn-outline !py-2 !px-4 text-sm">Preview sample ↗</a>
            @if($hasLatestOrder)
                <a href="{{ route('admin.invoice.preview') }}" target="_blank" rel="noopener" class="btn-outline !py-2 !px-4 text-sm">Preview latest order ↗</a>
            @endif
        </div>
    </div>

    <form action="{{ route('admin.invoice.update') }}" method="POST" enctype="multipart/form-data" class="max-w-4xl space-y-6 pb-28" x-data="{ dirty: false }" @input="dirty = true" @change="dirty = true" @submit="dirty = false">
        @csrf @method('PUT')

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        {{-- Company --}}
        <section class="admin-card admin-card-pad space-y-4">
            <div>
                <h2 class="font-bold text-slate-900">Company details (“From”)</h2>
                <p class="text-sm text-slate-500 mt-0.5">Printed in the top-left and the “From” block of every invoice.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach([
                    ['company_name', 'Brand name (large, top-left)', 'text'],
                    ['legal_name', 'Legal / business name', 'text'],
                    ['tagline', 'Tagline', 'text'],
                    ['website', 'Website', 'text'],
                    ['phone', 'Phone', 'text'],
                    ['email', 'Email', 'email'],
                ] as [$name, $label, $type])
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $label }}</label>
                        <input type="{{ $type }}" name="{{ $name }}" value="{{ $field($name) }}" class="input-field" @if($name === 'company_name' || $name === 'legal_name') required @endif>
                    </div>
                @endforeach
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Address</label>
                    <textarea name="address" rows="2" class="input-field">{{ $field('address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax ID name</label>
                    <input type="text" name="tax_label" value="{{ $field('tax_label') }}" class="input-field" placeholder="NTN, STRN, VAT…">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tax ID number</label>
                    <input type="text" name="tax_number" value="{{ $field('tax_number') }}" class="input-field" placeholder="Leave empty if you don't have one">
                </div>
            </div>
        </section>

        {{-- Branding --}}
        <section class="admin-card admin-card-pad space-y-4">
            <h2 class="font-bold text-slate-900">Branding &amp; layout</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2 flex flex-col sm:flex-row gap-4 items-start">
                    <div class="w-32 h-24 shrink-0 rounded-xl border border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden">
                        <img src="{{ $s['logo_path'] ? asset('storage/'.$s['logo_path']) : asset('images/logo.png') }}" alt="Invoice logo" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="space-y-2">
                        <label class="btn-outline !py-2 !px-4 text-sm cursor-pointer">{{ $s['logo_path'] ? 'Replace logo' : 'Upload a logo' }}
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                        </label>
                        <p class="text-xs text-slate-500">PNG, JPG or WebP up to 2 MB. {{ $s['logo_path'] ? '' : 'Showing your shop logo until you upload one.' }}</p>
                        @if($s['logo_path'])
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_logo" value="1" class="rounded text-sage-600"> Remove uploaded logo (use the shop logo)</label>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Accent colour</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="accent" value="{{ $field('accent') }}" class="h-11 w-16 rounded-lg border border-slate-200 bg-white p-1 cursor-pointer">
                        <span class="text-sm text-slate-500">Titles, table header and the total bar.</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Paper size</label>
                    <select name="paper" class="input-field">
                        <option value="a4" @selected($field('paper') === 'a4')>A4</option>
                        <option value="letter" @selected($field('paper') === 'letter')>Letter</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Invoice number prefix</label>
                    <input type="text" name="number_prefix" value="{{ $field('number_prefix') }}" maxlength="12" class="input-field" placeholder="INV-">
                    <p class="text-xs text-slate-400 mt-1">Printed as e.g. {{ $field('number_prefix') }}EKY-AB12CD34.</p>
                </div>
            </div>
        </section>

        {{-- What to show --}}
        <section class="admin-card admin-card-pad">
            <h2 class="font-bold text-slate-900 mb-1">What to show</h2>
            <p class="text-sm text-slate-500 mb-4">Switch parts of the invoice on or off.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                @foreach($toggles as $key => $label)
                    <div>
                        <input type="hidden" name="{{ $key }}" value="0">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $s[$key])) class="sr-only peer">
                            <span class="relative inline-block w-9 h-5 shrink-0 rounded-full bg-slate-300 peer-checked:bg-sage-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4"></span>
                            <span class="text-sm text-slate-700">{{ $label }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Wording --}}
        <section class="admin-card admin-card-pad space-y-4">
            <h2 class="font-bold text-slate-900">Wording</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Thank-you message</label>
                <input type="text" name="thank_you" value="{{ $field('thank_you') }}" maxlength="300" class="input-field">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Terms &amp; conditions <span class="text-slate-400 font-normal">(one point per line)</span></label>
                <textarea name="terms" rows="4" maxlength="1500" class="input-field">{{ $field('terms') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Bank / payment instructions <span class="text-slate-400 font-normal">(e.g. account title, IBAN, JazzCash/Easypaisa)</span></label>
                <textarea name="bank_details" rows="3" maxlength="800" class="input-field">{{ $field('bank_details') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Extra footer note <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" name="footer_note" value="{{ $field('footer_note') }}" maxlength="200" class="input-field">
            </div>
        </section>

        {{-- Copyright --}}
        <section class="admin-card admin-card-pad space-y-4">
            <div>
                <h2 class="font-bold text-slate-900">Copyright</h2>
                <p class="text-sm text-slate-500 mt-0.5">Printed at the bottom of every page and saved in the PDF's author information.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Owner</label>
                    <input type="text" name="copyright_holder" value="{{ $field('copyright_holder') }}" required maxlength="120" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Statement</label>
                    <input type="text" name="copyright_text" value="{{ $field('copyright_text') }}" maxlength="120" class="input-field">
                </div>
            </div>
            <p class="text-sm text-slate-600 rounded-lg bg-slate-50 px-3 py-2">Preview: <strong>© {{ now()->year }} {{ $field('copyright_holder') }}. {{ $field('copyright_text') }}</strong></p>
        </section>

        <div class="fixed bottom-0 inset-x-0 md:left-64 lg:left-72 z-40 bg-white/95 backdrop-blur border-t border-slate-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm" :class="dirty ? 'text-amber-600 font-medium' : 'text-slate-400'" x-text="dirty ? '● Unsaved changes' : 'All changes saved'"></p>
            <div class="flex flex-wrap gap-2">
                <button type="submit" name="preview" value="1" class="btn-outline !py-2.5 !px-5 text-sm">Save &amp; preview</button>
                <button type="submit" class="btn-primary !py-2.5 !px-8">Save</button>
            </div>
        </div>
    </form>

    <div class="max-w-4xl text-sm">
        <x-confirm-delete :action="route('admin.invoice.reset')" method="POST" variant="neutral" title="Reset the invoice?"
            message="Your company details, wording and switches will return to the original design. The invoice itself keeps working."
            confirm-label="Reset invoice" :trigger-show-icon="false">Reset to the original design</x-confirm-delete>
    </div>
@endsection
