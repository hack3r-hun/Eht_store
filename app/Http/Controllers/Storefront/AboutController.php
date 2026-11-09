<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;

class AboutController extends Controller
{
    public function index()
    {
        return view('storefront.about', ['page' => Page::forSlug('about')]);
    }
}
