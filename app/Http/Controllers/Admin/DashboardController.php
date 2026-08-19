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
        return view('admin.dashboard', [
            'posts' => Post::where('type', 'post')->count(),
            'pages' => Post::where('type', 'page')->count(),
            'published' => Post::where('status', 'published')->count(),
            'tags' => Tag::count(),
            'authors' => Author::count(),
            'comments' => Comment::count(),
            'comments_pending' => Comment::where('status', 'pending')->count(),
            'latest' => Post::orderByDesc('updated_at')->limit(8)->get(),
            'latestComments' => Comment::with('post:id,title,slug')->orderByDesc('created_at')->limit(6)->get(),
            'visitSummary' => $local->available() ? $local->summary() : ['ok' => false],
            'umamiConfigured' => $umami->configured(),
        ]);
    }
}
