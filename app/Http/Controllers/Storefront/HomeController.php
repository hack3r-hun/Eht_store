<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;

class HomeController extends Controller
{
    public function index()
    {
        return view('storefront.home', ['page' => Page::forSlug('home')]);
    }
}
