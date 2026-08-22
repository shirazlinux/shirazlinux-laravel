<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Settings
{
    private const CACHE_KEY = 'site_settings.all';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            // General
            'site_name' => 'شیرازلینوکس',
            'site_tagline' => 'جامعه نرم‌افزار آزاد شیراز',
            'site_description' => 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز — نشست، آموزش و ترویج آزادی کاربران.',
            'contact_email' => '',
            'footer_text' => 'جامعه نرم‌افزار آزاد شیراز',

            // SEO
            'seo_title_suffix' => 'جامعه نرم‌افزار آزاد',
            'seo_default_description' => 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز — نشست، آموزش و ترویج آزادی کاربران.',
            'seo_og_image' => 'media/slider/slide1.jpg',
            'seo_robots' => 'index,follow',
            'seo_twitter' => 'shirazlinux',
            'seo_canonical_base' => 'https://sudoshz.ir',
            'seo_keywords' => 'شیرازلینوکس, نرم‌افزار آزاد, لینوکس, شیراز, FOSS, جامعه نرم‌افزار آزاد, گنو/لینوکس',
            'seo_google_verification' => '',
            'seo_bing_verification' => '',
            'seo_yandex_verification' => '',
            'seo_locale' => 'fa_IR',
            'seo_og_type_default' => 'website',
            'seo_jsonld_enabled' => true,
            'seo_organization_name' => 'شیرازلینوکس',
            'seo_organization_logo' => 'media/website/icon.png',
            'seo_search_action' => true,

            // Display
            'posts_per_page' => 12,
            'default_theme' => 'system', // light | dark | system
            'home_show_history' => true,
            'home_show_projects' => true,
            'home_show_videos' => true,
            'home_show_edu' => true,
            'home_show_slider' => true,
            'comments_enabled' => true,

            // Homepage content
            'home_intro_title' => 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز',
            'home_intro_text' => "ما یک جامعه هستیم در شیراز؛ دور هم جمع می‌شویم، یاد می‌گیریم و نرم‌افزار آزاد را ترویج می‌کنیم.\nاگر دنبال نشست، آموزش، یا راهی برای شروع با گنو/لینوکس می‌گردی، جای درستی آمده‌ای.",
            'home_slider' => [], // filled by defaultSlides() when empty

            // Social
            'social_telegram' => 'https://t.me/sudoshz',
            'social_mastodon' => 'https://mastodon.social/@Shirazlinux',
            'social_matrix' => 'https://matrix.to/#/%23shirazlinux:matrix.org',
            'social_codeberg' => 'https://codeberg.org/shirazlinux',
            'social_github' => 'https://github.com/shirazlinux',
            'social_youtube' => 'https://www.youtube.com/@shirazlinux',
            'social_instagram' => 'https://www.instagram.com/shirazlinux',
            'social_x' => 'https://x.com/shirazlinux',
            'social_website' => 'https://sudoshz.ir',

            // Integrations (non-secret)
            'umami_website_id' => '5fd9997e-4f84-4028-942d-13783d46dcf6',
            'umami_script_url' => 'https://umami.sudoshz.ir/script.js',
            'umami_enabled' => true,

            // Maintenance
            'maintenance_mode' => false,
            'maintenance_message' => 'سایت به‌زودی برمی‌گردد. از شکیبایی شما سپاسگزاریم.',

            // robots.txt body
            'robots_txt' => "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /admin/\nDisallow: /analytics/\nDisallow: /preview\nDisallow: /preview/\nDisallow: /up\nSitemap: {sitemap}\n\nUser-agent: Googlebot\nAllow: /\nDisallow: /admin\nDisallow: /preview\n\nUser-agent: Googlebot-Image\nAllow: /media/\n",

            // Navigation (empty => NavMenu::defaults())
            'nav_menu' => [],
        ];
    }

    public static function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                Schema::create('site_settings', function ($table) {
                    $table->id();
                    $table->string('key')->unique();
                    $table->longText('value')->nullable();
                    $table->timestamps();
                });
            }
        } catch (Throwable) {
            // ignore during early boot failures
        }
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        self::ensureTable();
        $defaults = self::defaults();

        try {
            $stored = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function () {
                if (! Schema::hasTable('site_settings')) {
                    return [];
                }

                return SiteSetting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            $stored = [];
        }

        $out = $defaults;
        foreach ($stored as $key => $raw) {
            if (! array_key_exists($key, $defaults)) {
                $out[$key] = self::decode($raw);
                continue;
            }
            $out[$key] = self::cast($key, self::decode($raw), $defaults[$key]);
        }

        if (empty($out['home_slider']) || ! is_array($out['home_slider'])) {
            $out['home_slider'] = self::defaultSlides();
        }

        return $out;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (self::defaults()[$key] ?? null);
    }

    /**
     * Default homepage slider slides.
     *
     * @return list<array{image:string,title:string,alt:string,url:string,enabled:bool}>
     */
    public static function defaultSlides(): array
    {
        return [
            [
                'image' => 'media/slider/slide1.jpg',
                'title' => 'شبکه‌سازی با طعم قهوه',
                'alt' => 'دسته جمعی نشست سوم شیرازلینوکس',
                'url' => '/tags/event',
                'enabled' => true,
            ],
            [
                'image' => 'media/slider/slide2.jpg',
                'title' => 'دورهمی حضوری',
                'alt' => 'دسته جمعی افتتاحیه جامعه لینوکسی شیراز',
                'url' => '/tags/dorehami',
                'enabled' => true,
            ],
            [
                'image' => 'media/slider/slide3.jpg',
                'title' => 'کارگاه‌های تخصصی',
                'alt' => 'دسته جمعی همایش گنولینوکس شیراز',
                'url' => '/tags/workshop',
                'enabled' => true,
            ],
            [
                'image' => 'media/slider/slide4.jpg',
                'title' => 'همایش‌های نرم‌افزار آزاد',
                'alt' => 'همایش گنولینوکس شیرازلینوکس',
                'url' => '/tags/conference',
                'enabled' => true,
            ],
        ];
    }

    /**
     * Filled social profiles for footer / JSON-LD.
     *
     * @return list<array{key:string,label:string,url:string}>
     */
    public static function socialLinks(): array
    {
        $catalog = [
            'social_telegram' => 'تلگرام',
            'social_mastodon' => 'ماس‌تودون',
            'social_matrix' => 'ماتریکس',
            'social_youtube' => 'یوتیوب',
            'social_codeberg' => 'Codeberg',
            'social_github' => 'GitHub',
            'social_instagram' => 'اینستاگرام',
            'social_x' => 'اکس / توییتر',
            'social_website' => 'وب‌سایت',
        ];
        $out = [];
        foreach ($catalog as $key => $label) {
            $url = trim((string) self::get($key, ''));
            if ($url === '') {
                continue;
            }
            if ($key === 'social_telegram' && ! str_starts_with($url, 'http')) {
                $url = 'https://t.me/'.ltrim($url, '@');
            }
            if ($key === 'social_youtube' && ! str_starts_with($url, 'http')) {
                $url = 'https://www.youtube.com/'.ltrim($url, '@/');
            }
            if ($key === 'social_x' && ! str_starts_with($url, 'http')) {
                $url = 'https://x.com/'.ltrim($url, '@');
            }
            if ($key === 'social_instagram' && ! str_starts_with($url, 'http')) {
                $url = 'https://www.instagram.com/'.ltrim($url, '@/');
            }
            $out[] = [
                'key' => $key,
                'label' => $label,
                'url' => $url,
                'libre' => in_array($key, ['social_mastodon', 'social_matrix', 'social_codeberg'], true),
            ];
        }

        return $out;
    }

    /**
     * Footer only: free-software networks (not GitHub / Instagram / YouTube / X).
     * Telegram stays as the community channel.
     *
     * @return list<array{key:string,label:string,url:string,libre:bool}>
     */
    public static function footerSocialLinks(): array
    {
        $skip = ['social_website', 'social_github', 'social_instagram', 'social_youtube', 'social_x'];

        return array_values(array_filter(
            self::socialLinks(),
            fn (array $link) => ! in_array($link['key'], $skip, true)
        ));
    }

    public static function set(string $key, mixed $value): void
    {
        self::ensureTable();
        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => self::encode($value)],
        );
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $pairs */
    public static function setMany(array $pairs): void
    {
        self::ensureTable();
        foreach ($pairs as $key => $value) {
            SiteSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => self::encode($value)],
            );
        }
        Cache::forget(self::CACHE_KEY);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function encode(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function decode(?string $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $raw;
    }

    private static function cast(string $key, mixed $value, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }
        if (is_bool($default)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        if (is_int($default)) {
            return (int) $value;
        }
        if (is_array($default) && ! is_array($value)) {
            return $default;
        }

        return $value;
    }
}
