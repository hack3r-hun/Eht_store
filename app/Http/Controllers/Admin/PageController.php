<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::orderBy('title')->get();

        return view('admin.pages.index', compact('pages'));
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
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

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }
}
