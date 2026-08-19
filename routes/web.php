<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AuthorAdminController;
use App\Http\Controllers\Admin\BackupAdminController;
use App\Http\Controllers\Admin\CommentAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeAdminController;
use App\Http\Controllers\Admin\MediaAdminController;
use App\Http\Controllers\Admin\MenuAdminController;
use App\Http\Controllers\Admin\PostAdminController;
use App\Http\Controllers\Admin\RedirectAdminController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\TagAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Site\AnalyticsCollectController;
use App\Http\Controllers\Site\AuthorController;
use App\Http\Controllers\Site\CommentController;
use App\Http\Controllers\Site\FeedController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\PostController;
use App\Http\Controllers\Site\PreviewController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\TagController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/analytics/collect', AnalyticsCollectController::class)
    ->middleware('throttle:120,1')
    ->name('analytics.collect');
Route::get('/search', SearchController::class)->name('search');
Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/{slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::get('/authors/{slug}', [AuthorController::class, 'show'])->name('authors.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/feed.xml', FeedController::class)->name('feed');
Route::get('/rss.xml', FeedController::class)->name('rss');
Route::view('/feed', 'site.feed-info')->name('feed.info');

Route::get('/robots.txt', function () {
    $body = (string) setting('robots_txt', "User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: {sitemap}\n");
    $body = str_replace('{sitemap}', url('/sitemap.xml'), $body);
    // Always expose a clean sitemap line even if setting is stale
    if (! str_contains($body, 'Sitemap:')) {
        $body = rtrim($body)."\nSitemap: ".url('/sitemap.xml')."\n";
    }
    if (setting('maintenance_mode') && ! auth()->check()) {
        $body = "User-agent: *\nDisallow: /\n";
    }

    return response($body, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=3600');
})->name('robots');

Route::get('/humans.txt', function () {
    $body = implode("\n", [
        '/* TEAM */',
        'Community: شیرازلینوکس',
        'Site: https://sudoshz.ir/',
        'Location: Shiraz, Iran',
        '',
        '/* SITE */',
        'Standards: HTML5, CSS3, Schema.org',
        'Language: fa-IR',
        'Doctype: HTML5',
        'CMS: Laravel (custom)',
        '',
    ]);

    return response($body, 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('humans');

Route::get('/llms.txt', function () {
    $name = setting('site_name', 'شیرازلینوکس');
    $desc = setting('site_description', setting('seo_default_description', ''));
    $lines = [
        '# '.$name,
        '',
        '> '.$desc,
        '',
        '## Site',
        '- Home: '.url('/'),
        '- About: '.url('/about'),
        '- Tags: '.url('/tags'),
        '- Authors: '.url('/authors'),
        '- Search: '.url('/search'),
        '- RSS: '.url('/feed.xml'),
        '- Sitemap: '.url('/sitemap.xml'),
        '',
        '## Notes',
        '- Primary language: fa-IR (Persian)',
        '- Topic: free/libre software community in Shiraz, Iran',
        '- Content is free to share with attribution when license allows',
        '',
    ];

    return response(implode("\n", $lines), 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('llms');

Route::get('/site.webmanifest', function () {
    $name = setting('site_name', 'شیرازلینوکس');
    $manifest = [
        'name' => $name,
        'short_name' => $name,
        'description' => setting('site_description', ''),
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#f4f1ec',
        'theme_color' => '#F1592D',
        'lang' => 'fa',
        'dir' => 'rtl',
        'icons' => [
            [
                'src' => asset('media/website/webicon320.png'),
                'sizes' => '316x316',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('media/website/icon.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ],
    ];

    return response()->json($manifest, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ->header('Content-Type', 'application/manifest+json; charset=UTF-8')
        ->header('Cache-Control', 'public, max-age=86400');
})->name('webmanifest');

Route::get('/preview/{slug}', PreviewController::class)->name('preview.show');

// nested pages
Route::get('/about/{child}', function (string $child) {
    return app(PageController::class)->show($child);
})->name('pages.about.child');
Route::get('/transparency/{child}', function (string $child) {
    return app(PageController::class)->show($child);
})->name('pages.transparency.child');

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('stats', StatsController::class)->name('stats');
        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('home', [HomeAdminController::class, 'edit'])->name('home.edit');
        Route::post('home', [HomeAdminController::class, 'update'])->name('home.update');
        Route::get('menu', [MenuAdminController::class, 'edit'])->name('menu.edit');
        Route::post('menu', [MenuAdminController::class, 'update'])->name('menu.update');
        Route::post('menu/reset', [MenuAdminController::class, 'reset'])->name('menu.reset');
        Route::get('redirects', [RedirectAdminController::class, 'index'])->name('redirects.index');
        Route::post('redirects', [RedirectAdminController::class, 'store'])->name('redirects.store');
        Route::delete('redirects/{redirect}', [RedirectAdminController::class, 'destroy'])->name('redirects.destroy');
        Route::get('media', [MediaAdminController::class, 'index'])->name('media.index');
        Route::post('media', [MediaAdminController::class, 'store'])->name('media.store');
        Route::delete('media', [MediaAdminController::class, 'destroy'])->name('media.destroy');
        Route::get('users', [UserAdminController::class, 'index'])->name('users.index');
        Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::get('backup', [BackupAdminController::class, 'index'])->name('backup.index');
        Route::get('backup/export', [BackupAdminController::class, 'export'])->name('backup.export');
        Route::get('backup/sqlite', [BackupAdminController::class, 'downloadSqlite'])->name('backup.sqlite');
        Route::post('posts/bulk', [PostAdminController::class, 'bulk'])->name('posts.bulk');
        Route::resource('posts', PostAdminController::class)->except(['show']);
        Route::resource('tags', TagAdminController::class)->except(['show']);
        Route::resource('authors', AuthorAdminController::class)->except(['show']);
        Route::get('comments', [CommentAdminController::class, 'index'])->name('comments.index');
        Route::patch('comments/{comment}/status', [CommentAdminController::class, 'updateStatus'])->name('comments.status');
        Route::delete('comments/{comment}', [CommentAdminController::class, 'destroy'])->name('comments.destroy');
    });
});

// Free self-hosted comments (rate-limited)
Route::post('/{slug}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:8,10')
    ->name('comments.store');

// Content by slug last
// Separated projects (ada / free / delta) live outside Laravel on their own docroots/subdomains.
Route::get('/{slug}', function (string $slug) {
    $page = Post::query()->published()->pagesOnly()->where('slug', $slug)->first();
    if ($page) {
        return app(PageController::class)->show($slug);
    }

    return app(PostController::class)->show($slug);
})->where('slug', '^(?!tags$|authors$|search$|admin$|media$|css$|js$|microsites$|ada$|ada-zangeman$|free-software-history$|deltachat-list$|sitemap\.xml$|feed\.xml$|rss\.xml$|feed$|up$|preview$|robots\.txt$|llms\.txt$|humans\.txt$|site\.webmanifest$|analytics$).+')
  ->name('content.show');
