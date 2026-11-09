<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $content = Cache::remember('sitemap.xml', 300, function () {
            $products = Product::where('is_active', true)->get(['slug', 'updated_at']);

            $pages = \App\Models\Page::where('is_published', true)->get(['slug', 'updated_at'])->reject(fn ($page) => $page->isSystem());

            return view('sitemap', compact('products', 'pages'))->render();
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
