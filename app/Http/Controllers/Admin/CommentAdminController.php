<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CommentAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $comments = Comment::query()
            ->with(['post:id,title,slug', 'parent:id,author_name,body'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $counts = [
            'all' => Comment::count(),
            'approved' => Comment::where('status', 'approved')->count(),
            'pending' => Comment::where('status', 'pending')->count(),
            'spam' => Comment::where('status', 'spam')->count(),
            'trash' => Comment::where('status', 'trash')->count(),
        ];

        return view('admin.comments.index', compact('comments', 'status', 'counts'));
    }

    public function updateStatus(Request $request, Comment $comment): RedirectResponse
    {
        $status = $request->validate([
            'status' => 'required|in:approved,pending,spam,trash',
        ])['status'];

        $comment->update(['status' => $status]);

        return back()->with('ok', 'وضعیت نظر به‌روز شد.');
    }

    public function reply(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string|min:2|max:2000',
        ], [
            'body.required' => 'متن پاسخ را بنویسید.',
            'body.min' => 'پاسخ خیلی کوتاه است.',
            'body.max' => 'پاسخ خیلی طولانی است (حداکثر ۲۰۰۰ نویسه).',
        ]);

        $user = $request->user();
        $parentId = $comment->parent_id ?: $comment->id;
        $root = $comment->parent_id
            ? Comment::query()->where('id', $parentId)->where('post_id', $comment->post_id)->first()
            : $comment;
        if (! $root) {
            $root = $comment;
            $parentId = $comment->id;
        }

        if ($root->status === 'pending') {
            $root->update(['status' => 'approved']);
        }

        $reply = Comment::query()->create([
            'post_id' => $comment->post_id,
            'parent_id' => $parentId,
            'author_name' => $user?->name ?: 'شیرازلینوکس',
            'author_email' => $user?->email,
            'author_url' => url('/'),
            'body' => trim(strip_tags($data['body'])),
            'status' => 'approved',
            'is_staff' => true,
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        ActivityLog::record('comment.reply', $reply, 'پاسخ مدیر به نظر #'.$comment->id);

        $filter = $request->input('status', 'all');
        if (! in_array($filter, ['all', 'approved', 'pending', 'spam', 'trash'], true)) {
            $filter = 'all';
        }

        return redirect()
            ->to(route('admin.comments.index', ['status' => $filter]).'#comment-admin-'.$reply->id)
            ->with('ok', 'پاسخ ثبت شد و منتشر شد.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('ok', 'نظر حذف شد.');
    }
}
