<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** @var list<string> */
    private array $tabs = ['general', 'seo', 'display', 'social', 'integrations'];

    public function edit(Request $request): View
    {
        $tab = (string) $request->query('tab', 'general');
        if (! in_array($tab, $this->tabs, true)) {
            $tab = 'general';
        }

        return view('admin.settings.edit', [
            'tab' => $tab,
            'tabs' => $this->tabs,
            'settings' => Settings::all(),
            'defaults' => Settings::defaults(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = (string) $request->input('tab', 'general');
        if (! in_array($tab, $this->tabs, true)) {
            $tab = 'general';
        }

        $data = match ($tab) {
            'seo' => $request->validate([
                'seo_title_suffix' => 'nullable|string|max:120',
                'seo_default_description' => 'nullable|string|max:500',
                'seo_og_image' => 'nullable|string|max:500',
                'seo_robots' => 'nullable|string|max:80',
                'seo_twitter' => 'nullable|string|max:80',
                'seo_canonical_base' => 'nullable|string|max:255',
                'seo_keywords' => 'nullable|string|max:400',
                'seo_google_verification' => 'nullable|string|max:120',
                'seo_bing_verification' => 'nullable|string|max:120',
                'seo_yandex_verification' => 'nullable|string|max:120',
                'seo_locale' => 'nullable|string|max:20',
                'seo_organization_name' => 'nullable|string|max:120',
                'seo_organization_logo' => 'nullable|string|max:500',
            ]) + [
                'seo_jsonld_enabled' => $request->boolean('seo_jsonld_enabled'),
                'seo_search_action' => $request->boolean('seo_search_action'),
            ],
            'display' => $request->validate([
                'posts_per_page' => 'nullable|integer|min:3|max:48',
                'default_theme' => 'nullable|in:light,dark,system',
            ]) + [
                'home_show_history' => $request->boolean('home_show_history'),
                'home_show_projects' => $request->boolean('home_show_projects'),
                'home_show_videos' => $request->boolean('home_show_videos'),
                'home_show_edu' => $request->boolean('home_show_edu'),
                'home_show_slider' => $request->boolean('home_show_slider'),
                'comments_enabled' => $request->boolean('comments_enabled'),
            ],
            'social' => $request->validate([
                'social_website' => 'nullable|string|max:255',
                'social_telegram' => 'nullable|string|max:255',
                'social_mastodon' => 'nullable|string|max:255',
                'social_matrix' => 'nullable|string|max:255',
                'social_codeberg' => 'nullable|string|max:255',
                'social_github' => 'nullable|string|max:255',
                'social_youtube' => 'nullable|string|max:255',
                'social_instagram' => 'nullable|string|max:255',
                'social_x' => 'nullable|string|max:255',
            ]),
            'integrations' => $request->validate([
                'umami_website_id' => 'nullable|string|max:80',
                'umami_script_url' => 'nullable|string|max:255',
                'robots_txt' => 'nullable|string|max:5000',
            ]) + [
                'umami_enabled' => $request->boolean('umami_enabled'),
                'maintenance_mode' => $request->boolean('maintenance_mode'),
                'maintenance_message' => $request->input('maintenance_message', ''),
            ],
            default => $request->validate([
                'site_name' => 'nullable|string|max:120',
                'site_tagline' => 'nullable|string|max:200',
                'site_description' => 'nullable|string|max:800',
                'contact_email' => 'nullable|email|max:120',
                'footer_text' => 'nullable|string|max:255',
            ]),
        };

        // Normalize empty strings
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }

        Settings::setMany($data);

        if ($tab === 'integrations' && array_key_exists('robots_txt', $data)) {
            $body = str_replace('{sitemap}', url('/sitemap.xml'), (string) $data['robots_txt']);
            foreach (\App\Support\MediaStorage::writeRoots() as $root) {
                @file_put_contents(rtrim($root, '/').'/robots.txt', $body);
            }
        }

        return redirect()
            ->route('admin.settings.edit', ['tab' => $tab])
            ->with('ok', 'تنظیمات ذخیره شد.');
    }
}
