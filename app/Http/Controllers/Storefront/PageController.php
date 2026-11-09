<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;

/** Renders admin-created pages at /p/{slug}. Built-in pages have their own routes. */
class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        abort_if($page->isSystem(), 404);

        return view('storefront.page', compact('page'));
    }
}
