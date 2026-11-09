<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Cms\SectionSanitizer;
use App\Support\SiteMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function edit(): View
    {
        return view('admin.site.edit', [
            'config' => [
                'nav' => SiteMenu::nav(),
                'footer' => SiteMenu::footerLinks(),
                'socials' => SiteMenu::socials(),
            ],
            'topbar' => SiteMenu::topbarText(),
            'footerText' => SiteMenu::footerText(),
            'footerHeading' => SiteMenu::footerHeading(),
            'max' => ['nav' => SiteMenu::MAX_NAV, 'footer' => SiteMenu::MAX_FOOTER, 'socials' => SiteMenu::MAX_SOCIAL],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topbar' => ['nullable', 'string', 'max:120'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'footer_heading' => ['nullable', 'string', 'max:40'],
            'nav' => ['nullable', 'array', 'max:'.SiteMenu::MAX_NAV],
            'footer' => ['nullable', 'array', 'max:'.SiteMenu::MAX_FOOTER],
            'socials' => ['nullable', 'array', 'max:'.SiteMenu::MAX_SOCIAL],
            'nav.*.label' => ['nullable', 'string', 'max:40'],
            'nav.*.url' => ['nullable', 'string', 'max:300'],
            'footer.*.label' => ['nullable', 'string', 'max:60'],
            'footer.*.url' => ['nullable', 'string', 'max:300'],
            'socials.*.label' => ['nullable', 'string', 'max:40'],
            'socials.*.url' => ['nullable', 'string', 'max:300'],
        ]);

        Setting::set('site_topbar_text', strip_tags((string) ($data['topbar'] ?? '')));
        Setting::set('site_footer_text', strip_tags((string) ($data['footer_text'] ?? '')));
        Setting::set('site_footer_heading', strip_tags((string) ($data['footer_heading'] ?? '')) ?: 'Quick Links');

        foreach (['nav' => 'site_nav', 'footer' => 'site_footer_links', 'socials' => 'site_socials'] as $field => $key) {
            Setting::set($key, json_encode($this->cleanLinks($data[$field] ?? [])));
        }

        return back()->with('success', 'Navigation & footer saved.');
    }

    /** Drops blank rows and any link that is not a safe relative, http(s), mailto or tel address. */
    protected function cleanLinks(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'label' => trim(strip_tags((string) ($row['label'] ?? ''))),
                'url' => SectionSanitizer::url($row['url'] ?? ''),
            ])
            ->filter(fn ($row) => $row['label'] !== '' && $row['url'] !== '')
            ->values()
            ->all();
    }
}
