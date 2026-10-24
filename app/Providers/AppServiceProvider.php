<?php

namespace App\Providers;

use App\Models\CrewMember;
use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $helpers = app_path('helpers.php');
        if (is_file($helpers)) {
            require_once $helpers;
        }
    }

    public function boot(): void
    {
        if ($root = config('app.url')) {
            URL::forceRootUrl(rtrim($root, '/'));
        }
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        // Custom Persian pagination (no Tailwind/Bootstrap dependency).
        Paginator::defaultView('vendor.pagination.shiraz');
        Paginator::defaultSimpleView('vendor.pagination.simple-shiraz');
        Route::bind('crew', fn ($value) => CrewMember::query()->findOrFail($value));
        Gate::policy(Post::class, PostPolicy::class);
    }
}
