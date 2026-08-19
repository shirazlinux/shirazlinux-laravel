<?php

namespace App\Support;

class NavMenu
{
    /**
     * Default site navigation (matches current hardcoded menu).
     *
     * @return list<array{type:string,label:string,url?:string,children?:list<array{label:string,url:string}>}>
     */
    public static function defaults(): array
    {
        return [
            ['type' => 'link', 'label' => 'خانه', 'url' => '/'],
            [
                'type' => 'group',
                'label' => 'رویدادها',
                'children' => [
                    ['label' => 'دورهمی‌ها', 'url' => '/tags/dorehami'],
                    ['label' => 'نشست‌ها', 'url' => '/tags/event'],
                    ['label' => 'کارگاه‌ها', 'url' => '/tags/workshop'],
                    ['label' => 'همایش‌ها', 'url' => '/tags/conference'],
                    ['label' => 'گفتمان نرم‌افزار آزاد', 'url' => '/tags/freesoftwaretalks'],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'پست آزاد',
                'children' => [
                    ['label' => 'پست‌ها و مقالات آموزشی', 'url' => '/tags/post'],
                    ['label' => 'ویدیوها', 'url' => '/tags/videos'],
                ],
            ],
            ['type' => 'link', 'label' => 'دوره آموزشی', 'url' => '/tags/libre-learn'],
            ['type' => 'link', 'label' => 'راهنمای جامعه', 'url' => '/tags/free-software-community-guide'],
            [
                'type' => 'group',
                'label' => 'درباره ما',
                'children' => [
                    ['label' => 'درباره شیرازلینوکس', 'url' => '/about'],
                    ['label' => 'خط مشی اجرایی', 'url' => '/about/policy'],
                    ['label' => 'کد رفتاری شبکه‌های اجتماعی', 'url' => '/about/community-guidelines'],
                    ['label' => 'سیاست‌های حمایتی و تبلیغاتی', 'url' => '/about/promotion-policy'],
                    ['label' => 'گزارشات فعالیت', 'url' => '/tags/shirazlinux-reports'],
                    ['label' => 'گزارش مالی', 'url' => '/transparency'],
                    ['label' => 'تماس', 'url' => '/contact'],
                ],
            ],
            ['type' => 'link', 'label' => 'حمایت', 'url' => '/donate', 'cta' => true],
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function items(): array
    {
        $items = setting('nav_menu', null);
        if (! is_array($items) || $items === []) {
            return self::defaults();
        }

        return $items;
    }

    public static function resolveUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return url('/');
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (! str_starts_with($url, '/')) {
            $url = '/'.$url;
        }

        return url($url);
    }

    /**
     * Important destination pages for Google sitelinks / homepage quick links.
     * Flat, high-signal list (not every submenu leaf).
     *
     * @return list<array{name:string,url:string,description?:string}>
     */
    public static function sitelinks(): array
    {
        return [
            ['name' => 'درباره ما', 'url' => self::resolveUrl('/about'), 'description' => 'کی هستیم و چه می‌کنیم'],
            ['name' => 'نشست‌ها', 'url' => self::resolveUrl('/tags/event'), 'description' => 'نشست‌های حضوری و آنلاین'],
            ['name' => 'دورهمی‌ها', 'url' => self::resolveUrl('/tags/dorehami'), 'description' => 'دورهمی‌های ماهانه جامعه'],
            ['name' => 'مقالات', 'url' => self::resolveUrl('/tags/post'), 'description' => 'نوشته‌ها و آموزش‌ها'],
            ['name' => 'ویدیوها', 'url' => self::resolveUrl('/tags/videos'), 'description' => 'ویدیوهای آموزشی'],
            ['name' => 'راهنمای جامعه', 'url' => self::resolveUrl('/tags/free-software-community-guide'), 'description' => 'چطور به جامعه بپیوندی'],
            ['name' => 'نرم‌افزار آزاد', 'url' => self::resolveUrl('/what-is-free-software'), 'description' => 'آزادی در نرم‌افزار یعنی چه'],
            ['name' => 'حمایت', 'url' => self::resolveUrl('/donate'), 'description' => 'حمایت از جامعه'],
            ['name' => 'تماس', 'url' => self::resolveUrl('/contact'), 'description' => 'با ما در ارتباط باش'],
            ['name' => 'نویسندگان', 'url' => self::resolveUrl('/authors'), 'description' => 'نویسندگان جامعه'],
        ];
    }

    /**
     * Flatten nav items to SiteNavigationElement entries (max depth 1 children).
     *
     * @return list<array{name:string,url:string}>
     */
    public static function navigationFlat(int $limit = 12): array
    {
        $out = [];
        foreach (self::items() as $item) {
            if (($item['type'] ?? 'link') === 'group') {
                foreach ($item['children'] ?? [] as $child) {
                    $label = trim((string) ($child['label'] ?? ''));
                    $url = trim((string) ($child['url'] ?? ''));
                    if ($label === '' || $url === '') {
                        continue;
                    }
                    $out[] = ['name' => $label, 'url' => self::resolveUrl($url)];
                    if (count($out) >= $limit) {
                        return $out;
                    }
                }
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '' || $url === '/') {
                continue;
            }
            $out[] = ['name' => $label, 'url' => self::resolveUrl($url)];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
