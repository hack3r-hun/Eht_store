<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends ApiController
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return $this->success(new PageResource($page));
    }
}
