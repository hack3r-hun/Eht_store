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

            return view('sitemap', compact('products'))->render();
        });

        return response($content, 200)->header('Content-Type', 'application/xml');
    }
}
