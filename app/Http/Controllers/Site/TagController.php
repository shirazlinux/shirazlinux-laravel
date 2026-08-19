<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount(['posts' => fn ($q) => $q->published()->postsOnly()])
            ->orderBy('name')
            ->get()
            ->filter(fn ($t) => $t->posts_count > 0);

        return view('site.tags', compact('tags'));
    }

    public function show(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()
            ->published()
            ->postsOnly()
            ->with(['author', 'tags'])
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('site.tag', compact('tag', 'posts'));
    }
}
