<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Projects launched / hosted by ShirazLinux (covers under public/media/projects)
        $projects = [
            [
                'name' => 'یاور',
                'desc' => 'حمایت مالی جمعی از جامعه',
                'url' => 'https://donate.sudoshz.ir/',
                'tag' => 'حمایت',
                'emoji' => '💛',
                'image' => asset('media/projects/yavar-photo.jpg'),
            ],
            [
                'name' => 'میزگرد',
                'desc' => 'رویداد و ثبت‌نام',
                'url' => 'https://event.sudoshz.ir/',
                'tag' => 'رویداد',
                'emoji' => '🎟️',
                'image' => asset('media/projects/mizgard.svg'),
            ],
            [
                'name' => 'مترو شیراز',
                'desc' => 'برنامه سفر با مترو',
                'url' => 'https://metro.sudoshz.ir/',
                'tag' => 'برنامه',
                'emoji' => '🚇',
                'image' => asset('media/projects/metro-photo.jpg'),
            ],
            [
                'name' => 'گنولینوکس',
                'desc' => 'همایش و جامعه گنولینوکس شیراز',
                'url' => 'https://gnulinux.sudoshz.ir/',
                'tag' => 'همایش',
                'emoji' => '🐧',
                'image' => asset('media/projects/gnulinux.svg'),
            ],
            [
                'name' => 'دوآپس',
                'desc' => 'رویداد و محتوای دوآپس',
                'url' => 'https://devops.sudoshz.ir/',
                'tag' => 'فناوری',
                'emoji' => '⚙️',
                'image' => asset('media/projects/devops-photo.png'),
            ],
            [
                'name' => 'ترمینال',
                'desc' => 'فضای ترمینال جامعه',
                'url' => 'https://terminal.sudoshz.ir/',
                'tag' => 'ابزار',
                'emoji' => '💻',
                'image' => asset('media/projects/terminal.svg'),
            ],
            [
                'name' => 'جنبش نرم‌افزار آزاد',
                'desc' => 'درباره جنبش نرم‌افزار آزاد',
                'url' => 'https://free.sudoshz.ir/',
                'tag' => 'آزادی',
                'emoji' => '🕊️',
                'image' => asset('media/projects/free-photo.jpg'),
            ],
            [
                'name' => 'روز نرم‌افزار آزاد',
                'desc' => 'کمپین عشق به نرم‌افزار آزاد',
                'url' => 'https://ilovefs.sudoshz.ir/',
                'tag' => 'کمپین',
                'emoji' => '❤️',
                'image' => asset('media/projects/ilovefs.svg'),
            ],
            [
                'name' => 'جشن انتشار',
                'desc' => 'جشن انتشار اوبونتو در شیراز',
                'url' => 'https://hcc.sudoshz.ir/',
                'tag' => 'پروژه',
                'emoji' => '🧩',
                'image' => asset('media/projects/hcc.svg'),
            ],
            [
                'name' => 'دلتاچت',
                'desc' => 'پیام‌رسان آزاد روی ایمیل',
                'url' => 'https://delta.sudoshz.ir/',
                'tag' => 'پیام‌رسان',
                'emoji' => '💬',
                'image' => asset('media/projects/delta.svg'),
            ],
            [
                'name' => 'شروع گنولینوکس',
                'desc' => 'از کجا با گنو/لینوکس شروع کنی',
                'url' => url('/start-linux'),
                'tag' => 'آموزش',
                'emoji' => '🚀',
                'image' => asset('media/projects/startlinux.svg'),
            ],
            [
                'name' => 'ادا و زنگمن',
                'desc' => 'کتاب و ترجمه جمعی جامعه',
                'url' => 'https://sudoshz.ir/ada/',
                'tag' => 'کتاب',
                'emoji' => '📖',
                'image' => asset('media/projects/ada-photo.jpg'),
            ],
            [
                'name' => 'آمار بازدید',
                'desc' => 'آمار آزاد و محترم به حریم خصوصی',
                'url' => 'https://umami.sudoshz.ir/',
                'tag' => 'آمار',
                'emoji' => '📊',
                'image' => asset('media/projects/umami.svg'),
            ],
            [
                'name' => 'پایش سرویس‌ها',
                'desc' => 'وضعیت در دسترس بودن سرویس‌ها',
                'url' => 'https://uptim.sudoshz.ir/',
                'tag' => 'پایش',
                'emoji' => '🛰️',
                'image' => asset('media/projects/uptim.svg'),
            ],
        ];

        $tags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()->postsOnly()])
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get();

        // Free-software history trail (same curated order as Publii homepage)
        $historySlugs = [
            'fsf-history-redirect',
            'initial-announcement',
            'first-hackers-conference-1984',
            'manifesto',
            'what-is-free-software',
            'licenses',
            'fsf40',
        ];
        $historyBySlug = Post::query()
            ->published()
            ->whereIn('slug', $historySlugs)
            ->with(['author', 'tags'])
            ->get()
            ->keyBy('slug');
        $historyPosts = collect($historySlugs)
            ->map(fn (string $slug) => $historyBySlug->get($slug))
            ->filter()
            ->values();

        $pages = Post::query()
            ->published()
            ->pagesOnly()
            ->orderBy('title')
            ->get(['title', 'slug']);

        $rawSlides = setting('home_slider', []);
        if (! is_array($rawSlides) || $rawSlides === []) {
            $rawSlides = \App\Support\Settings::defaultSlides();
        }
        $slides = collect($rawSlides)
            ->filter(fn ($s) => is_array($s) && ! empty($s['enabled'] ?? true) && ! empty($s['image']))
            ->map(function (array $s) {
                $img = (string) $s['image'];
                $url = trim((string) ($s['url'] ?? ''));
                if ($url !== '' && ! str_starts_with($url, 'http')) {
                    if (! str_starts_with($url, '/')) {
                        $url = '/'.$url;
                    }
                    $url = url($url);
                }

                return [
                    'image' => str_starts_with($img, 'http') ? $img : asset(ltrim($img, '/')),
                    'title' => (string) ($s['title'] ?? ''),
                    'alt' => (string) ($s['alt'] ?? $s['title'] ?? ''),
                    'url' => $url,
                ];
            })
            ->values()
            ->all();
        if ($slides === []) {
            $slides = collect(\App\Support\Settings::defaultSlides())->map(fn ($s) => [
                'image' => asset(ltrim($s['image'], '/')),
                'title' => $s['title'],
                'alt' => $s['alt'],
                'url' => url($s['url']),
            ])->all();
        }

        // Video posts for discovery
        $videoPosts = Post::query()
            ->published()
            ->postsOnly()
            ->whereHas('tags', fn ($q) => $q->where('slug', 'videos'))
            ->with(['author', 'tags'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return view('site.home', compact(
            'projects',
            'tags',
            'historyPosts',
            'pages',
            'slides',
            'videoPosts'
        ));
    }
}
