<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use App\Services\LocalAnalytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnalyticsCollectController extends Controller
{
    public function __invoke(Request $request, LocalAnalytics $analytics): JsonResponse
    {
        $analytics->ensureTable();

        // Skip admin & bots lightly
        $ua = (string) $request->userAgent();
        if ($ua !== '' && preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua)) {
            return response()->json(['ok' => true, 'skipped' => 'bot']);
        }

        $data = $request->validate([
            'path' => 'required|string|max:500',
            'referrer' => 'nullable|string|max:500',
        ]);

        $path = $this->normalizePath($data['path']);
        if ($path === null) {
            return response()->json(['ok' => true, 'skipped' => 'path']);
        }

        $visitor = $request->cookie('sl_vid');
        if (! is_string($visitor) || strlen($visitor) < 8) {
            $visitor = (string) Str::uuid();
        }

        $session = $request->cookie('sl_sid');
        if (! is_string($session) || strlen($session) < 8) {
            $session = (string) Str::uuid();
        }

        $visitorHash = hash('sha256', $visitor.'|'.config('app.key'));
        $sessionHash = hash('sha256', $session.'|'.config('app.key'));

        $referrer = $this->normalizeReferrer($data['referrer'] ?? null, $request);

        PageView::query()->create([
            'path' => $path,
            'referrer' => $referrer,
            'visitor_hash' => $visitorHash,
            'session_hash' => $sessionHash,
            'user_agent' => Str::limit($ua, 500, ''),
            'created_at' => now(),
        ]);

        $response = response()->json(['ok' => true]);

        // 1 year visitor, 30 min session
        $response->cookie('sl_vid', $visitor, 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'Lax');
        $response->cookie('sl_sid', $session, 30, '/', null, $request->isSecure(), true, false, 'Lax');

        return $response;
    }

    private function normalizePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        // Allow absolute site path or full URL to our host
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parts = parse_url($path);
            $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        // Drop admin noise
        if (str_contains($path, '/admin') || str_contains($path, '/analytics/collect')) {
            return null;
        }
        // strip long query
        if (strlen($path) > 500) {
            $path = substr($path, 0, 500);
        }

        return $path;
    }

    private function normalizeReferrer(?string $referrer, Request $request): ?string
    {
        $referrer = trim((string) $referrer);
        if ($referrer === '') {
            return null;
        }
        $host = $request->getHost();
        $refHost = parse_url($referrer, PHP_URL_HOST);
        if ($refHost && str_contains((string) $refHost, $host)) {
            return null; // internal
        }

        return Str::limit($referrer, 500, '');
    }
}
