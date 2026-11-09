<x-mail::message>
# Good news — it's back!

**{{ $product->name }}** (size {{ $variant->size }}) is available again. Handmade pieces sell out quickly, so grab yours soon.

<x-mail::button :url="route('products.show', $product->slug)">
View {{ $product->name }}
</x-mail::button>

Thanks,<br>
{{ shop_name() }}
</x-mail::message>
