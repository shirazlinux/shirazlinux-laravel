<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Post::query()
            ->published()
            ->pagesOnly()
            ->where('slug', $slug)
            ->with(['author', 'approvedComments.approvedChildren'])
            ->firstOrFail();

        return view('site.page', ['post' => $page]);
    }
}
