<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaAdminController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $items = MediaStorage::listRecent(120);
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'items' => $items]);
        }

        return view('admin.media.index', compact('items'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'file' => 'required|image|max:8192|mimes:jpg,jpeg,png,gif,webp,avif',
        ]);

        $saved = MediaStorage::storeImage($request->file('file'), 'uploads');
        ActivityLog::record('media.upload', null, 'آپلود تصویر', $saved);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'path' => $saved['path'], 'url' => $saved['url']]);
        }

        return back()->with('ok', 'تصویر آپلود شد.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['path' => 'required|string|max:500']);
        $path = ltrim(str_replace('..', '', $data['path']), '/');
        if (! str_starts_with($path, 'media/uploads/')) {
            abort(403, 'فقط فایل‌های آپلود قابل حذف هستند.');
        }

        foreach (MediaStorage::writeRoots() as $root) {
            $full = rtrim($root, '/').'/'.$path;
            if (is_file($full)) {
                @unlink($full);
            }
        }
        ActivityLog::record('media.delete', null, 'حذف تصویر', ['path' => $path]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('ok', 'حذف شد.');
    }
}
