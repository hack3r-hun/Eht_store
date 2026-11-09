<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Support\Cms\PageDefaults;
use App\Support\Cms\SectionSanitizer;
use App\Support\Cms\SectionSchema;
use App\Support\Cms\SectionSchemaDefaults;
use App\Support\ImageProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $order = array_keys(PageDefaults::SYSTEM);

        $pages = Page::all()
            ->sortBy(fn (Page $page) => $page->isSystem() ? array_search($page->slug, $order) : 100 + $page->id)
            ->values();

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['slug' => Str::slug((string) ($request->slug ?: $request->title))]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug'), Rule::notIn(Page::RESERVED_SLUGS)],
        ], [
            'slug.not_in' => 'That address is reserved by the shop. Please choose another.',
            'slug.unique' => 'A page with that address already exists.',
        ]);

        $page = Page::create([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'meta' => [],
            'is_published' => false,
        ]);
        // Materialise the starter layout so it can be edited straight away.
        $page->update(['sections' => $page->resolvedSections()]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created as a draft. Add your content, then publish it.');
    }

    public function edit(Page $page): View
    {
        $sections = collect($page->resolvedSections())
            ->map(fn ($section) => [
                'id' => $section['id'] ?? 's'.Str::random(6),
                'type' => $section['type'],
                'enabled' => (bool) ($section['enabled'] ?? true),
                'data' => SectionSchemaDefaults::fill($section['type'], $section['data'] ?? []),
            ])
            ->filter(fn ($s) => SectionSchema::type($s['type']))
            ->values();

        return view('admin.pages.edit', [
            'page' => $page,
            'sections' => $sections,
            'schema' => SectionSchema::forEditor(),
            'categories' => Category::orderBy('name')->get(['name', 'slug']),
            'publicUrl' => $this->publicUrl($page),
            'usingDefaults' => ! is_array($page->sections),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        if (! $page->isSystem() && $request->filled('slug')) {
            $request->merge(['slug' => Str::slug((string) $request->slug)]);
        }

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'sections' => ['required', 'string', 'max:600000'],
            'is_published' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
            'meta.meta_title' => ['nullable', 'string', 'max:70'],
            'meta.meta_description' => ['nullable', 'string', 'max:200'],
            'meta.show_in_footer' => ['nullable', 'boolean'],
            'meta.form_title' => ['nullable', 'string', 'max:120'],
            'meta.form_subtitle' => ['nullable', 'string', 'max:250'],
            'meta.hours' => ['nullable', 'array', 'max:10'],
            'meta.hours.*.label' => ['nullable', 'string', 'max:60'],
            'meta.hours.*.value' => ['nullable', 'string', 'max:60'],
        ];

        if (! $page->isSystem()) {
            $rules['slug'] = ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug')->ignore($page->id), Rule::notIn(Page::RESERVED_SLUGS)];
        }

        $data = $request->validate($rules);

        $decoded = json_decode($data['sections'], true);

        if (! is_array($decoded)) {
            return back()->withInput()->with('error', 'The page layout could not be read. Please reload the page and try again.');
        }

        $meta = array_merge($page->meta ?? [], [
            'meta_title' => $data['meta']['meta_title'] ?? null,
            'meta_description' => $data['meta']['meta_description'] ?? null,
            'show_in_footer' => ! empty($data['meta']['show_in_footer']),
        ]);

        if ($page->slug === 'contact') {
            $meta['form_title'] = $data['meta']['form_title'] ?? null;
            $meta['form_subtitle'] = $data['meta']['form_subtitle'] ?? null;
            $meta['hours'] = collect($data['meta']['hours'] ?? [])
                ->filter(fn ($row) => filled($row['label'] ?? null) || filled($row['value'] ?? null))
                ->map(fn ($row) => ['label' => strip_tags((string) ($row['label'] ?? '')), 'value' => strip_tags((string) ($row['value'] ?? ''))])
                ->values()->all();
        }

        $page->update([
            'title' => strip_tags($data['title']),
            'slug' => $page->isSystem() ? $page->slug : $data['slug'],
            'sections' => SectionSanitizer::clean($decoded),
            'meta' => $meta,
            // Built-in pages are always live; only custom pages can be drafts.
            'is_published' => $page->isSystem() ? true : $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page saved.');
    }

    /** Throws away the saved layout so the built-in design returns. */
    public function reset(Page $page): RedirectResponse
    {
        abort_unless($page->isSystem(), 404);

        $page->update(['sections' => null]);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Layout reset to the original design.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_if($page->isSystem(), 403, 'Built-in pages cannot be deleted.');

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = ImageProcessor::store($request->file('image'), 'cms');

        return response()->json(['path' => $path, 'url' => cms_image($path)]);
    }

    protected function publicUrl(Page $page): string
    {
        return isset(PageDefaults::SYSTEM[$page->slug])
            ? url(PageDefaults::SYSTEM[$page->slug]['url'])
            : route('pages.show', $page->slug);
    }
}
