<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PolicyController extends Controller
{
    public function index()
    {
        return view('storefront.policies', ['page' => Page::forSlug('policies')]);
    }
}
