<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'items' => MediaStorage::listRecent(60),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:8192|mimes:jpg,jpeg,png,gif,webp,avif',
        ], [
            'file.required' => 'فایلی انتخاب نشده است.',
            'file.image' => 'فقط فایل تصویری مجاز است.',
            'file.max' => 'حجم تصویر حداکثر ۸ مگابایت باشد.',
        ]);

        $saved = MediaStorage::storeImage($request->file('file'), 'uploads');

        return response()->json([
            'ok' => true,
            'path' => $saved['path'],
            'url' => $saved['url'],
        ]);
    }
}
