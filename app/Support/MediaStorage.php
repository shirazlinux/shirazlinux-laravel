<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MediaStorage
{
    /**
     * Absolute directories where public media should be written
     * (app public/ and, on shared hosting, the live web root under public_html).
     *
     * @return list<string>
     */
    public static function writeRoots(): array
    {
        $roots = [public_path()];
        $home = dirname(base_path());
        foreach (['public_html', 'public_html/laravel-test'] as $rel) {
            $web = realpath($home.'/'.$rel);
            if ($web && $web !== public_path()) {
                $roots[] = $web;
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * Store an uploaded image under media/uploads/Y/m and return relative path.
     *
     * @return array{path:string,url:string}
     */
    public static function storeImage(UploadedFile $file, string $subdir = 'uploads'): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
            $ext = 'jpg';
        }
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = ($base !== '' ? $base : 'image').'-'.Str::lower(Str::random(8)).'.'.$ext;
        $relative = 'media/'.trim($subdir, '/').'/'.date('Y/m').'/'.$name;

        $binary = file_get_contents($file->getRealPath());
        foreach (self::writeRoots() as $root) {
            $full = rtrim($root, '/').'/'.$relative;
            $dir = dirname($full);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($full, $binary);
        }

        return [
            'path' => $relative,
            'url' => asset($relative),
        ];
    }

    /**
     * List recent uploaded images (from primary public path).
     *
     * @return list<array{path:string,url:string,name:string,mtime:int}>
     */
    public static function listRecent(int $limit = 48): array
    {
        $root = public_path('media/uploads');
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'], true)) {
                continue;
            }
            $full = $file->getPathname();
            $relative = 'media/uploads/'.ltrim(str_replace('\\', '/', substr($full, strlen($root))), '/');
            $files[] = [
                'path' => $relative,
                'url' => asset($relative),
                'name' => $file->getFilename(),
                'mtime' => $file->getMTime(),
            ];
        }
        usort($files, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return array_slice($files, 0, $limit);
    }
}
