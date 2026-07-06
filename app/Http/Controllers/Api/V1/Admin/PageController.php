<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends ApiController
{
    public function index(): JsonResponse
    {
        $pages = Page::orderBy('title')->get();

        return $this->success(PageResource::collection($pages));
    }

    public function show(Page $page): JsonResponse
    {
        return $this->success(new PageResource($page));
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'meta.hero_title' => ['nullable', 'string', 'max:255'],
            'meta.hero_subtitle' => ['nullable', 'string', 'max:500'],
        ]);

        $meta = array_merge($page->meta ?? [], $data['meta'] ?? []);
        $page->update([
            'title' => $data['title'],
            'content' => $data['content'] ?? $page->content,
            'meta' => $meta,
        ]);

        return $this->success(new PageResource($page->fresh()));
    }
}
