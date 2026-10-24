<?php

namespace App\Support;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;

/**
 * Photos from Publii-style <div class="gallery"> blocks in event posts.
 */
class EventGallery
{
    public static function eventSlugs(): array
    {
        return ['event', 'dorehami', 'workshop', 'conference', 'freesoftwaretalks'];
    }

    /**
     * @return list<array{src:string,thumb:string,alt:string,post_url:string,post_title:string}>
     */
    public static function itemsFromHtml(string $html, string $postUrl = '', string $postTitle = ''): array
    {
        $items = [];
        if ($html === '') {
            return $items;
        }

        $blocks = [];
        if (preg_match_all('#<div[^>]*class="[^"]*gallery[^"]*"[^>]*>(.*?)</div>#si', $html, $m)) {
            $blocks = $m[1];
        }
        $hay = $blocks !== [] ? implode("\n", $blocks) : $html;

        if (! preg_match_all('#<a[^>]+href="([^"]+)"[^>]*>\s*<img[^>]+>#i', $hay, $anchors, PREG_SET_ORDER)) {
            return $items;
        }

        $seen = [];
        foreach ($anchors as $a) {
            $href = html_entity_decode($a[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($href === '' || str_starts_with($href, 'file:')) {
                continue;
            }
            if (! preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $href)) {
                continue;
            }
            if (str_contains(strtolower($href), '-thumbnail.')) {
                continue;
            }
            if ($blocks === [] && ! str_contains($href, '/gallery/')) {
                continue;
            }

            $thumb = $href;
            if (preg_match('#src="([^"]+)"#i', $a[0], $sm)) {
                $thumb = html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $alt = $postTitle;
            if (preg_match('#alt="([^"]*)"#i', $a[0], $am)) {
                $alt = html_entity_decode($am[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: $postTitle;
            }

            $key = strtok($href, '?');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $items[] = [
                'src' => $href,
                'thumb' => $thumb,
                'alt' => $alt,
                'post_url' => $postUrl,
                'post_title' => $postTitle,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{src:string,thumb:string,alt:string,post_url:string,post_title:string}>
     */
    public static function collect(?Tag $tag = null, int $limit = 48): array
    {
        $q = Post::query()
            ->published()
            ->postsOnly()
            ->whereHas('tags', function ($t) use ($tag) {
                if ($tag) {
                    $t->where('tags.id', $tag->id);
                } else {
                    $t->whereIn('tags.slug', self::eventSlugs());
                }
            })
            ->orderByDesc('published_at');

        $out = [];
        foreach ($q->get(['id', 'slug', 'title', 'body']) as $post) {
            $url = url('/'.$post->slug);
            foreach (self::itemsFromHtml((string) $post->body, $url, $post->title) as $item) {
                $out[] = $item;
                if (count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }
}
