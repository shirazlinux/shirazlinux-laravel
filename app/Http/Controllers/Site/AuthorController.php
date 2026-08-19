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
            ->withCount(['posts' => fn ($q) => $q->published()->postsOnly()])
            ->orderBy('name')
            ->get()
            ->filter(fn ($a) => $a->posts_count > 0);

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

        return view('site.author', compact('author', 'posts'));
    }
}
