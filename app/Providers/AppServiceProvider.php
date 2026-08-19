<?php

namespace App\Providers;

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
    }
}
