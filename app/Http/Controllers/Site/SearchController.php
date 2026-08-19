<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $raw = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'all'); // all|post|page
        if (! in_array($type, ['all', 'post', 'page'], true)) {
            $type = 'all';
        }

        $q = $this->normalizeQuery($raw);
        $tokens = $this->tokens($q);

        $posts = Post::query()
            ->published()
            ->with(['author', 'tags'])
            ->when($type !== 'all', fn ($query) => $query->where('type', $type))
            ->when($tokens !== [], function ($query) use ($tokens) {
                // Each token must match somewhere (AND). Broad match; rank later.
                foreach ($tokens as $token) {
                    $like = '%'.$this->escapeLike($token).'%';
                    $query->where(function ($inner) use ($like) {
                        $inner->where('title', 'like', $like)
                            ->orWhere('excerpt', 'like', $like)
                            ->orWhere('slug', 'like', $like)
                            ->orWhere('meta_title', 'like', $like)
                            ->orWhere('meta_description', 'like', $like)
                            ->orWhere('body', 'like', $like)
                            ->orWhereHas('tags', fn ($t) => $t->where('name', 'like', $like)->orWhere('slug', 'like', $like))
                            ->orWhereHas('author', fn ($a) => $a->where('name', 'like', $like)->orWhere('slug', 'like', $like));
                    });
                }
            })
            // Without a query: don't dump entire catalog; show empty + suggestions
            ->when($tokens === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByDesc('published_at')
            ->limit(80)
            ->get();

        // Relevance ranking in PHP (SQLite-friendly, good for ~100s of posts)
        if ($tokens !== [] && $posts->isNotEmpty()) {
            $posts = $posts
                ->map(function (Post $post) use ($tokens, $q) {
                    $post->search_score = $this->score($post, $tokens, $q);
                    $post->search_snippet = $this->snippet($post, $tokens);

                    return $post;
                })
                ->sortByDesc(fn (Post $p) => [$p->search_score, optional($p->published_at)?->timestamp ?? 0])
                ->values();
        }

        // Manual pagination of ranked collection
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;
        $total = $posts->count();
        $items = $posts->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Related tags for empty/results sidebar
        $suggestedTags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')
            ->limit(16)
            ->get()
            ->filter(fn ($t) => $t->posts_count > 0);

        if ($tokens !== []) {
            $matchedTags = Tag::query()
                ->where(function ($w) use ($tokens) {
                    foreach ($tokens as $token) {
                        $like = '%'.$this->escapeLike($token).'%';
                        $w->orWhere('name', 'like', $like)->orWhere('slug', 'like', $like);
                    }
                })
                ->withCount(['posts' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->limit(8)
                ->get();
        } else {
            $matchedTags = collect();
        }

        $popularQueries = [
            'نرم‌افزار آزاد',
            'لینوکس',
            'نشست',
            'کارگاه',
            'ویدیو',
            'مانیفست',
            'جامعه',
            'گنو',
        ];

        return view('site.search', [
            'posts' => $paginator,
            'q' => $raw,
            'type' => $type,
            'tokens' => $tokens,
            'matchedTags' => $matchedTags,
            'suggestedTags' => $suggestedTags,
            'popularQueries' => $popularQueries,
            'total' => $total,
        ]);
    }

    private function normalizeQuery(string $q): string
    {
        // Unify Arabic/Persian yeh/kaf, collapse spaces
        $q = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $q);
        $q = preg_replace('/\s+/u', ' ', $q) ?? $q;

        return trim($q);
    }

    /** @return list<string> */
    private function tokens(string $q): array
    {
        if ($q === '' || mb_strlen($q) < 2) {
            return [];
        }

        $parts = preg_split('/[\s\x{200c}]+/u', $q) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) < 2) {
                continue;
            }
            // Skip ultra-common Persian stopwords for AND logic
            if (in_array($part, ['از', 'در', 'به', 'با', 'که', 'را', 'و', 'یا', 'این', 'آن'], true)) {
                continue;
            }
            $tokens[] = $part;
        }

        // If everything was stopword, fall back to full phrase
        if ($tokens === [] && mb_strlen($q) >= 2) {
            $tokens[] = $q;
        }

        return array_values(array_unique($tokens));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** @param list<string> $tokens */
    private function score(Post $post, array $tokens, string $full): int
    {
        $score = 0;
        $title = mb_strtolower((string) $post->title);
        $excerpt = mb_strtolower((string) $post->excerpt);
        $slug = mb_strtolower((string) $post->slug);
        $body = mb_strtolower(Str::limit(strip_tags((string) $post->body), 4000, ''));
        $tagBlob = mb_strtolower($post->tags->pluck('name')->implode(' ').' '.$post->tags->pluck('slug')->implode(' '));
        $author = mb_strtolower((string) ($post->author?->name ?? ''));
        $fullLower = mb_strtolower($full);

        // Exact / phrase boosts
        if ($fullLower !== '' && str_contains($title, $fullLower)) {
            $score += 120;
        }
        if ($fullLower !== '' && str_contains($excerpt, $fullLower)) {
            $score += 40;
        }

        foreach ($tokens as $token) {
            $t = mb_strtolower($token);
            if (str_contains($title, $t)) {
                $score += 50;
                if (str_starts_with($title, $t)) {
                    $score += 15;
                }
            }
            if (str_contains($slug, $t)) {
                $score += 25;
            }
            if (str_contains($excerpt, $t)) {
                $score += 18;
            }
            if (str_contains($tagBlob, $t)) {
                $score += 22;
            }
            if (str_contains($author, $t)) {
                $score += 12;
            }
            if (str_contains($body, $t)) {
                $score += 6;
            }
        }

        if ($post->type === 'page') {
            $score += 3;
        }
        if ($post->featured) {
            $score += 5;
        }

        return $score;
    }

    /** @param list<string> $tokens */
    private function snippet(Post $post, array $tokens): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($post->excerpt ?: $post->body))) ?? '');
        if ($text === '') {
            return '';
        }

        $lower = mb_strtolower($text);
        $pos = false;
        $hit = '';
        foreach ($tokens as $token) {
            $p = mb_stripos($lower, mb_strtolower($token));
            if ($p !== false) {
                $pos = $p;
                $hit = $token;
                break;
            }
        }

        if ($pos === false) {
            return Str::limit($text, 160);
        }

        $start = max(0, $pos - 50);
        $chunk = mb_substr($text, $start, 180);
        if ($start > 0) {
            $chunk = '…'.$chunk;
        }
        if ($start + 180 < mb_strlen($text)) {
            $chunk .= '…';
        }

        // Highlight first token occurrence (escaped)
        $safe = e($chunk);
        $safeHit = e($hit);
        if ($safeHit !== '') {
            $safe = preg_replace('/('.preg_quote($safeHit, '/').')/iu', '<mark>$1</mark>', $safe, 1) ?? $safe;
        }

        return $safe;
    }
}
