<?php

namespace App\Services;

use App\Models\PageView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LocalAnalytics
{
    public function ensureTable(): void
    {
        try {
            if (! Schema::hasTable('page_views')) {
                Schema::create('page_views', function ($table) {
                    $table->id();
                    $table->string('path', 500)->index();
                    $table->string('referrer', 500)->nullable();
                    $table->string('visitor_hash', 64)->index();
                    $table->string('session_hash', 64)->index();
                    $table->string('user_agent', 500)->nullable();
                    $table->timestamp('created_at')->useCurrent()->index();
                });
            }
        } catch (Throwable) {
            // ignore
        }
    }

    public function available(): bool
    {
        try {
            $this->ensureTable();

            return Schema::hasTable('page_views');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{ok:bool, stats:array, pageviews:list<array{x:string,y:int}>, pages:list<array{x:string,y:int}>, referrers:list<array{x:string,y:int}>, range:array}
     */
    public function dashboard(string $range = '7d'): array
    {
        $this->ensureTable();
        [$start, $end, $unit] = $this->rangeBounds($range);

        $base = PageView::query()->whereBetween('created_at', [$start, $end]);

        $pageviews = (clone $base)->count();
        $visitors = (clone $base)->distinct('visitor_hash')->count('visitor_hash');
        $visits = (clone $base)->distinct('session_hash')->count('session_hash');

        // bounce approx: sessions with only 1 pageview
        $sessionCounts = PageView::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('session_hash, COUNT(*) as c')
            ->groupBy('session_hash')
            ->get();
        $bounces = $sessionCounts->where('c', 1)->count();

        $series = $this->series($start, $end, $unit);
        $pages = PageView::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('path as x, COUNT(*) as y')
            ->groupBy('path')
            ->orderByDesc('y')
            ->limit(12)
            ->get()
            ->map(fn ($r) => ['x' => (string) $r->x, 'y' => (int) $r->y])
            ->all();

        $referrers = PageView::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->selectRaw('referrer as x, COUNT(*) as y')
            ->groupBy('referrer')
            ->orderByDesc('y')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['x' => (string) $r->x, 'y' => (int) $r->y])
            ->all();

        return [
            'ok' => true,
            'source' => 'local',
            'stats' => [
                'pageviews' => $pageviews,
                'visitors' => $visitors,
                'visits' => $visits,
                'bounces' => $bounces,
                'totaltime' => 0,
            ],
            'pageviews' => $series,
            'pages' => $pages,
            'referrers' => $referrers,
            'browsers' => [],
            'countries' => [],
            'range' => [
                'key' => $range,
                'startAt' => $start->getTimestampMs(),
                'endAt' => $end->getTimestampMs(),
                'unit' => $unit,
            ],
        ];
    }

    /**
     * @return array{ok:bool, today:array, week:array}
     */
    public function summary(): array
    {
        $this->ensureTable();
        $today = $this->dashboard('24h')['stats'];
        $week = $this->dashboard('7d')['stats'];

        return [
            'ok' => true,
            'today' => $today,
            'week' => $week,
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon,2:string}
     */
    private function rangeBounds(string $range): array
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

        return [$start, $end, $unit];
    }

    /**
     * @return list<array{x:string,y:int}>
     */
    private function series(Carbon $start, Carbon $end, string $unit): array
    {
        $format = $unit === 'hour' ? '%Y-%m-%d %H:00' : '%Y-%m-%d';
        $rows = PageView::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("strftime('{$format}', created_at) as x, COUNT(*) as y")
            ->groupBy('x')
            ->orderBy('x')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->x] = (int) $r->y;
        }

        $out = [];
        $cursor = $start->copy()->startOf($unit === 'hour' ? 'hour' : 'day');
        $limit = $unit === 'hour' ? 24 : 90;
        $i = 0;
        while ($cursor <= $end && $i < $limit) {
            $key = $cursor->format($unit === 'hour' ? 'Y-m-d H:00' : 'Y-m-d');
            $out[] = ['x' => $key, 'y' => $map[$key] ?? 0];
            $cursor->add($unit === 'hour' ? 1 : 1, $unit === 'hour' ? 'hour' : 'day');
            $i++;
        }

        return $out;
    }
}
