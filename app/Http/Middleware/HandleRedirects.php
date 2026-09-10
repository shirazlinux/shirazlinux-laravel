<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        // Built-in legacy redirects (Publii / old paths) — always on
        $legacy = $this->legacyTarget($path);
        if ($legacy !== null) {
            return redirect()->to($legacy, 301);
        }

        // Strip .html / .htm → clean URL (Publii static leftovers)
        if (preg_match('#^(.+)\.html?$#i', $path, $m)) {
            $clean = $m[1] !== '' ? $m[1] : '/';

            return redirect()->to(url($clean), 301);
        }

        // Database-managed redirects
        try {
            if (Schema::hasTable('redirects')) {
                $candidates = array_unique([
                    $path,
                    rtrim($path, '/') ?: '/',
                    '/'.ltrim($request->getPathInfo(), '/'),
                    rawurldecode($path),
                ]);
                $candidates = array_map(function ($p) {
                    $p = rawurldecode((string) $p);
                    if (str_contains($p, '?')) {
                        $p = strstr($p, '?', true) ?: $p;
                    }

                    return $p === '' ? '/' : $p;
                }, $candidates);

                $redirect = Redirect::query()
                    ->where('enabled', true)
                    ->whereIn('from_path', $candidates)
                    ->first();

                if ($redirect) {
                    $to = $redirect->to_url;
                    if (! str_starts_with($to, 'http')) {
                        $to = url($to);
                    }

                    return redirect()->to($to, $redirect->status_code ?: 301);
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return $next($request);
    }

    private function legacyTarget(string $path): ?string
    {
        $map = [
            '/index.html' => '/',
            '/index.htm' => '/',
            '/index.php' => '/',
            '/home' => '/',
            '/home/' => '/',
            '/feed.html' => '/feed.xml',
            '/rss' => '/feed.xml',
            '/rss.xml' => '/feed.xml',
            '/sitemap.html' => '/sitemap.xml',
            '/search.html' => '/search',
            // common Publii aliases
            '/fsf-history-redirect' => '/free-software-history/',
            '/fsf-history-redirect/' => '/free-software-history/',
            '/free-software-history' => '/free-software-history/',
            '/gnulinux-conf/' => '/gnulinux-conf',
            // Separated projects → own docroot / subdomain
            '/ada-zangeman' => '/ada/',
            '/deltachat-list' => 'https://delta.sudoshz.ir/',
        ];

        if (isset($map[$path])) {
            $to = $map[$path];

            return str_starts_with($to, 'http') ? $to : url($to);
        }

        // Nested old microsite paths: /ada-zangeman/book → /ada/book
        if (str_starts_with($path, '/ada-zangeman/')) {
            return url('/ada/'.substr($path, strlen('/ada-zangeman/')));
        }
        if (str_starts_with($path, '/deltachat-list/')) {
            return 'https://delta.sudoshz.ir/'.ltrim(substr($path, strlen('/deltachat-list/')), '/');
        }

        return null;
    }
}
