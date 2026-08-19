<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $comments = Comment::query()
            ->with(['post:id,title,slug'])
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

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('ok', 'نظر حذف شد.');
    }
}
