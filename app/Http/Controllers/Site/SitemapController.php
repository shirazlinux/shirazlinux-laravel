<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->orderByDesc('updated_at')
            ->get(['slug', 'type', 'title', 'featured_image', 'updated_at', 'published_at']);

        $tags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('slug')
            ->get();

        $authors = Author::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('slug')
            ->get();

        $latest = $posts->first();
        $homeLastmod = optional($latest?->updated_at)->toAtomString()
            ?: optional($latest?->published_at)->toAtomString()
            ?: now()->toAtomString();

        $lines = [];
        $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            .' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
            .' xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        $this->url($lines, url('/'), $homeLastmod, 'daily', '1.0', [
            ['loc' => Seo::defaultOgImage(), 'title' => setting('site_name', 'شیرازلینوکس')],
        ]);

        // High-value static destinations (brand sitelinks candidates)
        foreach ([
            ['/about', '0.85', 'monthly'],
            ['/contact', '0.7', 'monthly'],
            ['/donate', '0.75', 'monthly'],
            ['/transparency', '0.7', 'monthly'],
            ['/what-is-free-software', '0.8', 'monthly'],
            ['/tags', '0.65', 'weekly'],
            ['/authors', '0.55', 'weekly'],
            ['/search', '0.3', 'monthly'],
        ] as [$path, $pri, $freq]) {
            $this->url($lines, url($path), $homeLastmod, $freq, $pri);
        }

        foreach ($posts as $p) {
            // avoid duplicate if already listed above as static key page
            if (in_array($p->slug, ['about', 'contact', 'donate', 'transparency', 'what-is-free-software'], true)) {
                continue;
            }
            $lastmod = optional($p->updated_at)->toAtomString()
                ?: optional($p->published_at)->toAtomString()
                ?: now()->toAtomString();
            $freq = $p->type === 'page' ? 'monthly' : 'weekly';
            $priority = $p->type === 'page' ? '0.7' : '0.8';
            $images = [];
            if ($p->featured_image) {
                $images[] = [
                    'loc' => Seo::absolute($p->featured_image),
                    'title' => $p->title,
                ];
            }
            $this->url($lines, url('/'.$p->slug), $lastmod, $freq, $priority, $images);
        }

        foreach ($tags as $t) {
            if ((int) $t->posts_count < 1) {
                continue;
            }
            $images = [];
            $cover = method_exists($t, 'featuredImagePath') ? $t->featuredImagePath() : $t->featured_image;
            if ($cover) {
                $images[] = [
                    'loc' => Seo::absolute($cover),
                    'title' => $t->name,
                ];
            }
            $lastmod = optional($t->updated_at)->toAtomString() ?: $homeLastmod;
            $this->url($lines, url('/tags/'.$t->slug), $lastmod, 'weekly', '0.55', $images);
        }

        foreach ($authors as $a) {
            if ((int) $a->posts_count < 1) {
                continue;
            }
            $images = [];
            if ($a->avatar) {
                $images[] = [
                    'loc' => Seo::absolute($a->avatar),
                    'title' => $a->name,
                ];
            }
            $lastmod = optional($a->updated_at)->toAtomString() ?: $homeLastmod;
            $this->url($lines, url('/authors/'.$a->slug), $lastmod, 'monthly', '0.45', $images);
        }

        // Separated projects (ada / free / delta) are hosted outside the main CMS — not listed here.

        $lines[] = '</urlset>';

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * @param  list<string>  $lines
     * @param  list<array{loc:string,title?:string}>  $images
     */
    private function url(
        array &$lines,
        string $loc,
        string $lastmod,
        string $changefreq,
        string $priority,
        array $images = [],
    ): void {
        $lines[] = '  <url>';
        $lines[] = '    <loc>'.e($loc).'</loc>';
        $lines[] = '    <lastmod>'.e($lastmod).'</lastmod>';
        $lines[] = '    <changefreq>'.e($changefreq).'</changefreq>';
        $lines[] = '    <priority>'.e($priority).'</priority>';
        $lines[] = '    <xhtml:link rel="alternate" hreflang="fa" href="'.e($loc).'"/>';
        foreach ($images as $img) {
            if (empty($img['loc'])) {
                continue;
            }
            $lines[] = '    <image:image>';
            $lines[] = '      <image:loc>'.e($img['loc']).'</image:loc>';
            if (! empty($img['title'])) {
                $lines[] = '      <image:title>'.e($img['title']).'</image:title>';
            }
            $lines[] = '    </image:image>';
        }
        $lines[] = '  </url>';
    }
}
