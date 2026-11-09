@php
    /** @var \App\Models\Order $order */
    $accent = $s['accent'];
    $hide = (bool) $order->gift_hide_prices;
    $paid = $order->payment_status === \App\Enums\PaymentStatus::Paid;
    $addr = $order->shipping_address ?? [];
    $billName = $order->contact_name ?: ($addr['full_name'] ?? $order->user?->name ?? 'Customer');
    $billEmail = $order->contact_email ?: ($addr['email'] ?? $order->user?->email);
    $billPhone = $order->contact_phone ? '+'.$order->contact_phone : ($addr['phone'] ?? null);
    $showShip = $s['show_shipping_address'];
    $money = fn ($v) => shop_money($v);
    $dateFmt = $order->created_at?->format('d M Y') ?? now()->format('d M Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $hide ? 'Packing slip' : 'Invoice' }} {{ $invoiceNumber }} — {{ $s['company_name'] }}</title>
    <style>
        @page { margin: 34px 38px 92px 38px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #36454F; line-height: 1.45; }
        table { border-collapse: collapse; }
        .w100 { width: 100%; }
        .muted { color: #8a94a0; }
        .small { font-size: 8.5px; }
        .brand { font-size: 19px; font-weight: bold; color: {{ $accent }}; }
        .doc-title { font-size: 26px; font-weight: bold; color: {{ $accent }}; letter-spacing: 2px; }
        .rule { height: 3px; background: {{ $accent }}; margin: 14px 0 16px 0; }
        .label { font-size: 8px; font-weight: bold; color: {{ $accent }}; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
        .party td { vertical-align: top; padding-right: 14px; }
        .strong { font-weight: bold; color: #1f2933; }
        .strip td { background: #f7f3ec; padding: 7px 10px; border-right: 3px solid #ffffff; }
        .strip .k { font-size: 7.5px; color: #8a94a0; text-transform: uppercase; letter-spacing: 0.5px; }
        .strip .v { font-size: 10px; font-weight: bold; color: #1f2933; }
        .items { margin-top: 16px; }
        .items th { background: {{ $accent }}; color: #ffffff; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 8px; text-align: left; }
        .items td { padding: 8px; border-bottom: 1px solid #ece6db; vertical-align: top; }
        .items tr.alt td { background: #fcfaf6; }
        .r { text-align: right; }
        .c { text-align: center; }
        .totals td { padding: 5px 8px; }
        .totals .grand td { background: {{ $accent }}; color: #ffffff; font-size: 12px; font-weight: bold; padding: 9px 8px; }
        .tag { display: inline-block; padding: 4px 12px; font-size: 10px; font-weight: bold; letter-spacing: 1.5px; border: 2px solid; }
        .tag-paid { color: #2f8f5b; border-color: #2f8f5b; }
        .tag-due { color: #c27a1b; border-color: #c27a1b; }
        .box { border: 1px dashed {{ $accent }}; background: #fffaf7; padding: 8px 10px; margin-top: 14px; }
        .block { margin-top: 16px; }
        .thanks { text-align: center; font-size: 11px; color: {{ $accent }}; margin-top: 22px; font-weight: bold; }
        .sig { margin-top: 34px; }
        .sig .line { border-top: 1px solid #36454F; width: 180px; padding-top: 4px; font-size: 8.5px; color: #8a94a0; text-align: center; }
        .footer { position: fixed; left: 0; right: 0; bottom: -62px; height: 56px; border-top: 1px solid #ddd5c6; padding-top: 8px; text-align: center; font-size: 8px; color: #8a94a0; }
        .footer .copy { font-size: 8.5px; color: #5b6772; font-weight: bold; margin-top: 3px; }
    </style>
</head>
<body>

    {{-- Footer repeats on every page: contact line + copyright --}}
    <div class="footer">
        @if($s['show_footer_contact'])
            {{ collect([$s['legal_name'], $s['address'], $s['phone'], $s['email'], $s['website']])->filter()->implode('  ·  ') }}<br>
        @endif
        @if($s['footer_note'])<span>{{ $s['footer_note'] }}</span><br>@endif
        @if($s['show_computer_generated'])<span>This is a computer-generated {{ $hide ? 'document' : 'invoice' }} and does not require a signature.</span><br>@endif
        <div class="copy">{{ $copyright }}</div>
    </div>

    {{-- Header --}}
    <table class="w100">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                @if($logo)<img src="{{ $logo }}" alt="" style="height: 54px; margin-bottom: 6px;"><br>@endif
                <span class="brand">{{ $s['company_name'] }}</span>
                @if($s['show_tagline'] && $s['tagline'])<div class="muted">{{ $s['tagline'] }}</div>@endif
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-title">{{ $hide ? 'PACKING SLIP' : 'INVOICE' }}</div>
                <table align="right" style="margin-top: 6px;">
                    <tr><td class="muted" style="padding: 1px 8px 1px 0; text-align: right;">{{ $hide ? 'Reference' : 'Invoice no.' }}</td><td class="strong" style="text-align: right;">{{ $invoiceNumber }}</td></tr>
                    <tr><td class="muted" style="padding: 1px 8px 1px 0; text-align: right;">Date</td><td class="strong" style="text-align: right;">{{ $dateFmt }}</td></tr>
                    <tr><td class="muted" style="padding: 1px 8px 1px 0; text-align: right;">Order</td><td class="strong" style="text-align: right;">{{ $order->order_number }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- From / Bill to / Ship to --}}
    <table class="w100 party">
        <tr>
            <td style="width: {{ $showShip ? '33%' : '50%' }};">
                <div class="label">From</div>
                <span class="strong">{{ $s['legal_name'] }}</span><br>
                @if($s['address']){{ $s['address'] }}<br>@endif
                @if($s['phone']){{ $s['phone'] }}<br>@endif
                @if($s['email']){{ $s['email'] }}<br>@endif
                @if($s['website']){{ $s['website'] }}<br>@endif
                @if($s['show_tax_number'] && $s['tax_number'])<span class="muted">{{ $s['tax_label'] }}:</span> <span class="strong">{{ $s['tax_number'] }}</span>@endif
            </td>
            <td style="width: {{ $showShip ? '33%' : '50%' }};">
                <div class="label">Bill to</div>
                <span class="strong">{{ $billName }}</span><br>
                @if($s['show_customer_contact'])
                    @if($billEmail){{ $billEmail }}<br>@endif
                    @if($billPhone){{ $billPhone }}<br>@endif
                @endif
            </td>
            @if($showShip)
                <td style="width: 34%;">
                    <div class="label">Deliver to</div>
                    <span class="strong">{{ $addr['full_name'] ?? $billName }}</span><br>
                    @if(! empty($addr['address_line'])){{ $addr['address_line'] }}<br>@endif
                    {{ collect([$addr['city'] ?? null, $addr['state'] ?? null, $addr['postal_code'] ?? null])->filter()->implode(', ') }}<br>
                    @if(! empty($addr['phone'])){{ $addr['phone'] }}@endif
                </td>
            @endif
        </tr>
    </table>

    {{-- Order strip --}}
    @if($s['show_payment_info'])
        <table class="w100 strip" style="margin-top: 14px;">
            <tr>
                <td style="width: 25%;"><div class="k">Order date</div><div class="v">{{ $dateFmt }}</div></td>
                <td style="width: 25%;"><div class="k">Payment method</div><div class="v">{{ $order->payment_method->label() }}</div></td>
                <td style="width: 25%;"><div class="k">Payment status</div><div class="v">{{ $order->payment_status->label() }}</div></td>
                <td style="width: 25%;"><div class="k">Order status</div><div class="v">{{ $order->status->label() }}</div></td>
            </tr>
        </table>
    @endif

    {{-- Items --}}
    <table class="w100 items">
        <thead>
            <tr>
                <th style="width: 6%;" class="c">#</th>
                <th>Item</th>
                <th style="width: 9%;" class="c">Qty</th>
                @unless($hide)
                    <th style="width: 17%;" class="r">Unit price</th>
                    <th style="width: 17%;" class="r">Amount</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
                <tr class="{{ $i % 2 ? 'alt' : '' }}">
                    <td class="c muted">{{ $i + 1 }}</td>
                    <td>
                        <span class="strong">{{ $item->product_name }}</span>
                        @if($s['show_size'] && $item->size)<span class="muted"> — Size {{ $item->size }}</span>@endif
                        @if($s['show_sku'] && $item->product_sku)<br><span class="muted small">SKU {{ $item->product_sku }}</span>@endif
                    </td>
                    <td class="c">{{ $item->quantity }}</td>
                    @unless($hide)
                        <td class="r">{{ $money($item->unit_price) }}</td>
                        <td class="r strong">{{ $money($item->line_total) }}</td>
                    @endunless
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    @unless($hide)
        <table class="w100" style="margin-top: 10px;">
            <tr>
                <td style="width: 52%; vertical-align: top; padding-top: 6px;">
                    @if($s['show_status_stamp'])
                        <span class="tag {{ $paid ? 'tag-paid' : 'tag-due' }}">{{ $paid ? 'PAID' : 'PAYMENT DUE' }}</span>
                    @endif
                </td>
                <td style="width: 48%;">
                    <table class="w100 totals">
                        <tr><td class="muted">Subtotal</td><td class="r">{{ $money($order->subtotal) }}</td></tr>
                        @if($order->discount > 0)<tr><td class="muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="r">− {{ $money($order->discount) }}</td></tr>@endif
                        <tr><td class="muted">Shipping</td><td class="r">{{ $order->shipping > 0 ? $money($order->shipping) : 'Free' }}</td></tr>
                        @if($order->gift_wrap)<tr><td class="muted">Gift wrap</td><td class="r">{{ $money($order->gift_wrap_fee) }}</td></tr>@endif
                        @if($order->tax > 0)<tr><td class="muted">Tax</td><td class="r">{{ $money($order->tax) }}</td></tr>@endif
                        <tr class="grand"><td>Total</td><td class="r">{{ $money($order->total) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    @else
        <div class="box small" style="margin-top: 12px;">Prices are hidden on this document because the order is a gift.</div>
    @endunless

    {{-- Gift message / notes --}}
    @if($s['show_gift_message'] && $order->gift_wrap && $order->gift_message)
        <div class="box"><div class="label">Gift message</div><i>“{{ $order->gift_message }}”</i></div>
    @endif
    @if($s['show_order_notes'] && $order->notes)
        <div class="block"><div class="label">Customer notes</div>{{ $order->notes }}</div>
    @endif

    {{-- Payment instructions + terms --}}
    @if(($s['show_bank_details'] && $s['bank_details']) || ($s['show_terms'] && $s['terms']))
        <table class="w100 block">
            <tr>
                @if($s['show_bank_details'] && $s['bank_details'])
                    <td style="width: 46%; vertical-align: top; padding-right: 16px;">
                        <div class="label">How to pay</div>
                        <div class="small">{!! nl2br(e($s['bank_details'])) !!}</div>
                    </td>
                @endif
                @if($s['show_terms'] && $s['terms'])
                    <td style="vertical-align: top;">
                        <div class="label">Terms &amp; conditions</div>
                        <div class="small muted">{!! nl2br(e($s['terms'])) !!}</div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    @if($s['show_thank_you'] && $s['thank_you'])
        <div class="thanks">{{ $s['thank_you'] }}</div>
    @endif

    @if($s['show_signature'])
        <table class="w100 sig"><tr><td></td><td style="width: 200px;"><div class="line">Authorised signature — {{ $s['legal_name'] }}</div></td></tr></table>
    @endif

</body>
</html>
