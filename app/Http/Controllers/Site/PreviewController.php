<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $post = Post::query()->where('slug', $slug)->with(['author', 'tags'])->firstOrFail();

        // Admins always can preview; others need signed URL
        if (! auth()->check()) {
            if (! $request->hasValidSignature()) {
                abort(403, 'لینک پیش‌نمایش نامعتبر یا منقضی است.');
            }
        }

        if ($post->type === 'page') {
            return view('site.page', [
                'post' => $post,
                'isPreview' => true,
            ]);
        }

        $related = collect();

        return view('site.post', [
            'post' => $post,
            'related' => $related,
            'isPreview' => true,
        ]);
    }
}
