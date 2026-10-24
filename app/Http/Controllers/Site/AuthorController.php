<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $authors = Author::query()
            ->withCount([
                'posts' => fn ($q) => $q->published()->postsOnly(),
                'contributions',
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn ($a) => $a->posts_count > 0 || $a->contributions_count > 0);

        return view('site.authors', compact('authors'));
    }

    public function show(string $slug): View
    {
        $author = Author::where('slug', $slug)->firstOrFail();
        $posts = $author->posts()
            ->published()
            ->postsOnly()
            ->with(['tags'])
            ->orderByDesc('published_at')
            ->paginate(12);

        $contributed = $author->contributions()
            ->with(['post' => fn ($q) => $q->published()->postsOnly()->with('tags')])
            ->get()
            ->map(fn ($c) => $c->post)
            ->filter()
            ->unique('id')
            ->reject(fn ($p) => $posts->contains('id', $p->id))
            ->values();

        return view('site.author', compact('author', 'posts', 'contributed'));
    }
}
