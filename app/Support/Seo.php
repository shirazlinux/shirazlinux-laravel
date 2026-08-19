<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Shared SEO helpers for public pages.
 */
class Seo
{
    public static function absolute(?string $pathOrUrl): string
    {
        $pathOrUrl = trim((string) $pathOrUrl);
        if ($pathOrUrl === '') {
            return '';
        }
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        return url(ltrim($pathOrUrl, '/'));
    }

    public static function defaultOgImage(): string
    {
        $configured = trim((string) setting('seo_og_image', ''));
        if ($configured !== '') {
            return self::absolute($configured);
        }

        // Prefer a wide slide, then logo
        foreach (['media/slider/slide1.jpg', 'media/website/logo.png'] as $fallback) {
            $full = public_path($fallback);
            if (is_file($full)) {
                return self::absolute($fallback);
            }
            // shared-hosting web root dual path
            $web = dirname(base_path()).'/public_html/'.$fallback;
            if (is_file($web)) {
                return self::absolute($fallback);
            }
        }

        return self::absolute('media/website/logo.png');
    }

    public static function description(?string $text, int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        if ($text === '') {
            return (string) setting('seo_default_description', setting('site_description', ''));
        }

        return Str::limit($text, $limit, '…');
    }

    /**
     * Canonical URL without tracking query params.
     */
    public static function canonical(?string $url = null): string
    {
        $url = $url ?: url()->current();
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return url()->current();
        }

        $base = ($parts['scheme'] ?? 'https').'://'.$parts['host'];
        if (! empty($parts['port'])) {
            $base .= ':'.$parts['port'];
        }
        $path = $parts['path'] ?? '/';
        // strip trailing slash except root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $keep = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $qs);
            // keep meaningful pagination / filter only
            foreach (['page', 'type'] as $k) {
                if (isset($qs[$k]) && $qs[$k] !== '' && $qs[$k] !== '1' && $qs[$k] !== 'all') {
                    $keep[$k] = $qs[$k];
                }
            }
        }

        $canonical = $base.$path;
        if ($keep) {
            $canonical .= '?'.http_build_query($keep);
        }

        $canonicalBase = rtrim((string) setting('seo_canonical_base', ''), '/');
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($canonicalBase !== '' && $appUrl !== '' && str_starts_with($canonical, $appUrl)) {
            $canonical = $canonicalBase.substr($canonical, strlen($appUrl));
        }

        return $canonical;
    }

    /** @return list<string> */
    public static function sameAs(): array
    {
        $keys = [
            'social_telegram', 'social_mastodon', 'social_matrix',
            'social_codeberg', 'social_github', 'social_youtube',
            'social_instagram', 'social_x', 'social_website',
        ];
        $out = [];
        foreach ($keys as $k) {
            $v = trim((string) setting($k, ''));
            if ($v === '') {
                continue;
            }
            if ($k === 'social_telegram' && ! str_starts_with($v, 'http')) {
                $v = 'https://t.me/'.ltrim($v, '@');
            }
            if ($k === 'social_youtube' && ! str_starts_with($v, 'http')) {
                $v = 'https://www.youtube.com/'.ltrim($v, '@/');
            }
            if ($k === 'social_x' && ! str_starts_with($v, 'http')) {
                $v = 'https://x.com/'.ltrim($v, '@');
            }
            if ($k === 'social_instagram' && ! str_starts_with($v, 'http')) {
                $v = 'https://www.instagram.com/'.ltrim($v, '@/');
            }
            if ($k === 'social_github' && ! str_starts_with($v, 'http')) {
                $v = 'https://github.com/'.ltrim($v, '@/');
            }
            $out[] = $v;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<array{name:string,url:string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbJsonLd(array $items): array
    {
        $elements = [];
        $pos = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($name === '' || $url === '') {
                continue;
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $name,
                'item' => $url,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * SiteNavigationElement list — helps Google understand main destinations (sitelinks).
     *
     * @return list<array<string, mixed>>
     */
    public static function siteNavigationElements(): array
    {
        $items = NavMenu::sitelinks();
        $out = [];
        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($name === '' || $url === '') {
                continue;
            }
            $el = [
                '@type' => 'SiteNavigationElement',
                'name' => $name,
                'url' => $url,
            ];
            if (! empty($item['description'])) {
                $el['description'] = $item['description'];
            }
            $out[] = $el;
        }

        return $out;
    }

    /**
     * ItemList of primary destinations for homepage / brand SERP.
     *
     * @return array<string, mixed>
     */
    public static function primaryLinksItemList(): array
    {
        $elements = [];
        $pos = 1;
        foreach (NavMenu::sitelinks() as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($name === '' || $url === '') {
                continue;
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $pos++,
                'name' => $name,
                'url' => $url,
                'item' => $url,
            ];
        }

        return [
            '@type' => 'ItemList',
            '@id' => url('/').'#primary-links',
            'name' => 'پیوندهای مهم '.setting('site_name', 'شیرازلینوکس'),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($elements),
            'itemListElement' => $elements,
        ];
    }
}
