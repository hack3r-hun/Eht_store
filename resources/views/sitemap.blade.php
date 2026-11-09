<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ route('products.index') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    <url><loc>{{ route('about') }}</loc><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc>{{ route('contact') }}</loc><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc>{{ route('custom-order.create') }}</loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
    <url><loc>{{ route('policies') }}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    @foreach($pages as $page)
    <url><loc>{{ route('pages.show', $page->slug) }}</loc><lastmod>{{ $page->updated_at->toAtomString() }}</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>
    @endforeach
    @foreach($products as $product)
    <url><loc>{{ route('products.show', $product->slug) }}</loc><lastmod>{{ $product->updated_at->toAtomString() }}</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
    @endforeach
</urlset>
