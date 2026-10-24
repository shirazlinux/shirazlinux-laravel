<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Services\LocalAnalytics;
use App\Services\UmamiClient;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(LocalAnalytics $local, UmamiClient $umami): View
    {
        $user = auth()->user();
        $posts = Post::query()->where('type', 'post');
        $published = Post::query()->where('status', 'published')->where('type', 'post');
        $latest = Post::query()->orderByDesc('updated_at');
        if ($user && $user->isWriter()) {
            $posts = $posts->ownedBy($user);
            $published = $published->ownedBy($user);
            $latest = $latest->ownedBy($user)->where('type', 'post');
        }

        return view('admin.dashboard', [
            'posts' => $posts->count(),
            'pages' => $user?->isWriter() ? 0 : Post::where('type', 'page')->count(),
            'published' => $published->count(),
            'tags' => Tag::count(),
            'authors' => Author::count(),
            'comments' => $user?->isWriter() ? 0 : Comment::count(),
            'comments_pending' => $user?->isWriter() ? 0 : Comment::where('status', 'pending')->count(),
            'latest' => $latest->limit(8)->get(),
            'latestComments' => $user?->isWriter()
                ? collect()
                : Comment::with('post:id,title,slug')->orderByDesc('created_at')->limit(6)->get(),
            'visitSummary' => $user?->isWriter() ? ['ok' => false] : ($local->available() ? $local->summary() : ['ok' => false]),
            'umamiConfigured' => $umami->configured(),
        ]);
    }
}
