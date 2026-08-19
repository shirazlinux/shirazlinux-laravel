<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupAdminController extends Controller
{
    public function index()
    {
        return view('admin.backup.index');
    }

    public function export(): StreamedResponse
    {
        ActivityLog::record('backup.export', null, 'خروجی پشتیبان JSON');

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'settings' => Settings::all(),
            'posts' => Post::query()->with('tags:id,slug')->orderBy('id')->get()->map(function (Post $p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'type' => $p->type,
                    'status' => $p->status,
                    'excerpt' => $p->excerpt,
                    'body' => $p->body,
                    'featured_image' => $p->featured_image,
                    'meta_title' => $p->meta_title,
                    'meta_description' => $p->meta_description,
                    'published_at' => optional($p->published_at)?->toIso8601String(),
                    'tags' => $p->tags->pluck('slug')->all(),
                ];
            })->all(),
            'settings_raw' => SiteSetting::query()->pluck('value', 'key')->all(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $name = 'shirazlinux-backup-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $name, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function downloadSqlite(): Response
    {
        $path = database_path('database.sqlite');
        abort_unless(is_file($path), 404);
        ActivityLog::record('backup.sqlite', null, 'دانلود SQLite');

        return response()->download($path, 'database-'.now()->format('Ymd-His').'.sqlite');
    }
}
