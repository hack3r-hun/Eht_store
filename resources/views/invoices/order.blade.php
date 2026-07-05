<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 10px; margin-bottom: 20px; }
        .brand { color: #0d9488; font-size: 24px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0fdfa; }
        .total { font-size: 16px; font-weight: bold; text-align: right; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ shop_name() }}</div>
        <div>Invoice: {{ $order->order_number }}</div>
        <div>Date: {{ $order->created_at->format('M d, Y') }}</div>
    </div>

    <p><strong>Ship To:</strong><br>
        {{ $order->shipping_address['full_name'] ?? '' }}<br>
        {{ $order->shipping_address['phone'] ?? '' }}<br>
        {{ $order->shipping_address['address_line'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }}
    </p>

    <table>
        <thead>
            <tr><th>Item</th><th>SKU</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product_sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ shop_money($item->unit_price) }}</td>
                    <td>{{ shop_money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">Total: {{ shop_money($order->total) }}</div>
</body>
</html>
