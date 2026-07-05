<x-mail::message>
# Order Confirmed

Thank you for your order **{{ $order->order_number }}**.

**Total:** {{ shop_money($order->total) }}  
**Payment:** {{ $order->payment_method->label() }}  
**Status:** {{ $order->status->label() }}

## Items
@foreach($order->items as $item)
- {{ $item->product_name }} × {{ $item->quantity }} — {{ shop_money($item->line_total) }}
@endforeach

Thanks,<br>
{{ shop_name() }}
</x-mail::message>
