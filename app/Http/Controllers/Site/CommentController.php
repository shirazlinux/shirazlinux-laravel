<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail();

        // Honeypot: bots fill "website_hp"
        if (filled($request->input('website_hp'))) {
            return redirect()
                ->to(url('/'.$post->slug).'#comments')
                ->with('comment_ok', 'نظر شما ثبت شد.');
        }

        $data = $request->validate([
            'author_name' => 'required|string|min:2|max:80',
            'author_email' => 'nullable|email|max:190',
            'author_url' => 'nullable|url|max:255',
            'body' => 'required|string|min:3|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ], [
            'author_name.required' => 'نام را بنویسید.',
            'body.required' => 'متن نظر خالی است.',
            'body.max' => 'نظر خیلی طولانی است (حداکثر ۲۰۰۰ نویسه).',
            'author_email.email' => 'ایمیل معتبر نیست.',
            'author_url.url' => 'آدرس وب‌سایت معتبر نیست (با https://).',
        ]);

        // Parent must belong to same post and be approved
        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = Comment::query()
                ->where('id', $parentId)
                ->where('post_id', $post->id)
                ->where('status', 'approved')
                ->whereNull('parent_id') // only one level
                ->first();
            if (! $parent) {
                $parentId = null;
            }
        }

        $body = trim(strip_tags($data['body']));
        $name = trim(strip_tags($data['author_name']));
        $status = $this->decideStatus($body, $data['author_url'] ?? null, $request->ip());

        Comment::create([
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'author_name' => $name,
            'author_email' => $data['author_email'] ?? null,
            'author_url' => $data['author_url'] ?? null,
            'body' => $body,
            'status' => $status,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $msg = $status === 'approved'
            ? 'نظر شما منتشر شد. سپاس از مشارکت آزادتان.'
            : 'نظر شما دریافت شد و پس از بررسی کوتاه منتشر می‌شود.';

        return redirect()
            ->to(url('/'.$post->slug).'#comments')
            ->with('comment_ok', $msg);
    }

    private function decideStatus(string $body, ?string $url, ?string $ip): string
    {
        $lower = mb_strtolower($body);
        $linkCount = preg_match_all('/https?:\/\/|www\./i', $body) ?: 0;
        if ($url) {
            $linkCount++;
        }

        // Too many links → pending
        if ($linkCount >= 3) {
            return 'pending';
        }

        $spamHints = ['viagra', 'casino', 'crypto pump', 'forex', 'seo service', 'buy followers'];
        foreach ($spamHints as $hint) {
            if (str_contains($lower, $hint)) {
                return 'spam';
            }
        }

        // Very short + has link → pending
        if ($linkCount > 0 && mb_strlen($body) < 20) {
            return 'pending';
        }

        return 'approved';
    }
}
