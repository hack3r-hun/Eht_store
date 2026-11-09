<x-mail::message>
# New custom order request

**From:** {{ $order->name }} ({{ $order->email }}@if($order->phone), {{ $order->phone }}@endif)
**Item:** {{ $order->item_type }}
**Colours:** {{ $order->colors ?: '—' }}
**Size:** {{ $order->size ?: '—' }}
**Budget:** {{ $order->budget ?: '—' }}
**Needed by:** {{ $order->needed_by?->format('d M Y') ?? '—' }}

{{ $order->description }}

<x-mail::button :url="route('admin.custom-orders.index')">
Open in admin
</x-mail::button>
</x-mail::message>
