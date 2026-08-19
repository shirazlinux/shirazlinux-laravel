<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(string $slug): View
    {
        $post = Post::query()
            ->published()
            ->postsOnly()
            ->where('slug', $slug)
            ->with(['author', 'tags', 'approvedComments.approvedChildren'])
            ->firstOrFail();

        $related = Post::query()
            ->published()
            ->postsOnly()
            ->where('id', '!=', $post->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $post->tags->pluck('id')))
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        return view('site.post', compact('post', 'related'));
    }
}
