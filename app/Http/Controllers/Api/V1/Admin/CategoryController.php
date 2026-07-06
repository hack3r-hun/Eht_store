<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::with('parent')->withCount('products')->orderBy('sort_order')->paginate(20);

        return $this->paginated($categories, CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
            MediaUrl::forgetExists($data['image']);
        }

        $category = Category::create($data);

        return $this->success(new CategoryResource($category), 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->load('parent')->loadCount('products');

        return $this->success(new CategoryResource($category));
    }

    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $category->slug;
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            MediaUrl::deleteLocalFile($category->image);
            $data['image'] = $request->file('image')->store('categories', 'public');
            MediaUrl::forgetExists($data['image']);
        }

        $category->update($data);

        return $this->success(new CategoryResource($category->fresh()));
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->error('Cannot delete category with products.', 422);
        }

        MediaUrl::deleteLocalFile($category->image);
        $category->delete();

        return $this->success(['message' => 'Category deleted.']);
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
