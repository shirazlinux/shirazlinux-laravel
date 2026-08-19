<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Umami Analytics API client (self-hosted: umami.sudoshz.ir).
 */
class UmamiClient
{
    public function configured(): bool
    {
        return filled(config('services.umami.token'))
            && filled(config('services.umami.website_id'))
            && filled(config('services.umami.base_url'));
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.umami.base_url'), '/');
    }

    public function websiteId(): string
    {
        return (string) config('services.umami.website_id');
    }

    /**
     * @return array{ok:bool, error?:string, stats?:array, pageviews?:array, pages?:array, referrers?:array, browsers?:array, countries?:array, range?:array}
     */
    public function dashboard(string $range = '7d'): array
    {
        if (! $this->configured()) {
            return [
                'ok' => false,
                'error' => 'توکن یا شناسهٔ وب‌سایت Umami تنظیم نشده است.',
            ];
        }

        $range = in_array($range, ['24h', '7d', '30d', '90d'], true) ? $range : '7d';
        $cacheKey = 'umami.dashboard.'.$this->websiteId().'.'.$range;

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($range) {
            try {
                [$startAt, $endAt, $unit] = $this->rangeToTimestamps($range);
                $id = $this->websiteId();

                $stats = $this->get("/api/websites/{$id}/stats", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                ]);
                $pageviews = $this->get("/api/websites/{$id}/pageviews", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'unit' => $unit,
                    'timezone' => config('services.umami.timezone', 'Asia/Tehran'),
                ]);
                $pages = $this->get("/api/websites/{$id}/metrics", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'type' => 'url',
                    'limit' => 10,
                ]);
                $referrers = $this->get("/api/websites/{$id}/metrics", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'type' => 'referrer',
                    'limit' => 8,
                ]);
                $browsers = $this->get("/api/websites/{$id}/metrics", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'type' => 'browser',
                    'limit' => 6,
                ]);
                $countries = $this->get("/api/websites/{$id}/metrics", [
                    'startAt' => $startAt,
                    'endAt' => $endAt,
                    'type' => 'country',
                    'limit' => 6,
                ]);

                return [
                    'ok' => true,
                    'stats' => $this->normalizeStats($stats),
                    'pageviews' => $this->normalizeSeries($pageviews),
                    'pages' => $this->normalizeMetrics($pages),
                    'referrers' => $this->normalizeMetrics($referrers),
                    'browsers' => $this->normalizeMetrics($browsers),
                    'countries' => $this->normalizeMetrics($countries),
                    'range' => [
                        'key' => $range,
                        'startAt' => $startAt,
                        'endAt' => $endAt,
                        'unit' => $unit,
                    ],
                ];
            } catch (\Throwable $e) {
                Log::warning('Umami API error: '.$e->getMessage());

                return [
                    'ok' => false,
                    'error' => 'خطا در دریافت آمار از Umami: '.$e->getMessage(),
                ];
            }
        });
    }

    /**
     * Lightweight stats for dashboard cards (24h + 7d).
     *
     * @return array{ok:bool, error?:string, today?:array, week?:array}
     */
    public function summary(): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return Cache::remember('umami.summary.'.$this->websiteId(), now()->addMinutes(5), function () {
            try {
                [$s1, $e1] = $this->rangeToTimestamps('24h');
                [$s7, $e7] = $this->rangeToTimestamps('7d');
                $id = $this->websiteId();

                return [
                    'ok' => true,
                    'today' => $this->normalizeStats($this->get("/api/websites/{$id}/stats", [
                        'startAt' => $s1, 'endAt' => $e1,
                    ])),
                    'week' => $this->normalizeStats($this->get("/api/websites/{$id}/stats", [
                        'startAt' => $s7, 'endAt' => $e7,
                    ])),
                ];
            } catch (\Throwable $e) {
                Log::warning('Umami summary error: '.$e->getMessage());

                return ['ok' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * @return array{0:int,1:int,2:string} startAt ms, endAt ms, unit
     */
    private function rangeToTimestamps(string $range): array
    {
        $end = now();
        $unit = 'day';
        $start = match ($range) {
            '24h' => $end->copy()->subDay(),
            '30d' => $end->copy()->subDays(30),
            '90d' => $end->copy()->subDays(90),
            default => $end->copy()->subDays(7),
        };
        if ($range === '24h') {
            $unit = 'hour';
        }

        return [
            (int) $start->getTimestampMs(),
            (int) $end->getTimestampMs(),
            $unit,
        ];
    }

    private function get(string $path, array $query = []): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.umami.token'))
            ->acceptJson()
            ->timeout(12)
            ->get($path, $query);

        if ($response->status() === 401 || $response->status() === 403) {
            throw new \RuntimeException('دسترسی غیرمجاز — توکن Umami را بررسی کنید.');
        }

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status().' از Umami');
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Umami returns { pageviews: {value, prev}, visitors: {...}, ... } or flat numbers depending on version.
     *
     * @return array{pageviews:int,visitors:int,visits:int,bounces:int,totaltime:int}
     */
    private function normalizeStats(array $data): array
    {
        $pick = function (string $key) use ($data): int {
            if (! array_key_exists($key, $data)) {
                return 0;
            }
            $v = $data[$key];
            if (is_array($v)) {
                return (int) ($v['value'] ?? $v['x'] ?? 0);
            }

            return (int) $v;
        };

        return [
            'pageviews' => $pick('pageviews'),
            'visitors' => $pick('visitors'),
            'visits' => $pick('visits'),
            'bounces' => $pick('bounces'),
            'totaltime' => $pick('totaltime'),
        ];
    }

    /**
     * @return list<array{x:string,y:int}>
     */
    private function normalizeSeries(array $data): array
    {
        // Newer: { pageviews: [{x,y}], sessions: [...] }
        if (isset($data['pageviews']) && is_array($data['pageviews'])) {
            $out = [];
            foreach ($data['pageviews'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $out[] = [
                    'x' => (string) ($row['x'] ?? $row['t'] ?? ''),
                    'y' => (int) ($row['y'] ?? $row['c'] ?? 0),
                ];
            }

            return $out;
        }

        // Flat list
        if (array_is_list($data)) {
            $out = [];
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $out[] = [
                    'x' => (string) ($row['x'] ?? $row['t'] ?? ''),
                    'y' => (int) ($row['y'] ?? $row['c'] ?? 0),
                ];
            }

            return $out;
        }

        return [];
    }

    /**
     * @return list<array{x:string,y:int}>
     */
    private function normalizeMetrics(array $data): array
    {
        $list = array_is_list($data) ? $data : ($data['data'] ?? []);
        $out = [];
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'x' => (string) ($row['x'] ?? $row['name'] ?? '—'),
                'y' => (int) ($row['y'] ?? $row['c'] ?? $row['total'] ?? 0),
            ];
        }

        return $out;
    }
}
