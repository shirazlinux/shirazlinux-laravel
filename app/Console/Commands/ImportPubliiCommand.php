<?php

namespace App\Console\Commands;

use App\Models\Author;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;

class ImportPubliiCommand extends Command
{
    protected $signature = 'publii:import
        {--db= : Path to Publii db.sqlite}
        {--media= : Path to Publii input/media directory}
        {--fresh : Wipe content tables first}';

    protected $description = 'Import posts, pages, tags, authors from a Publii SQLite database';

    public function handle(): int
    {
        $dbPath = $this->option('db') ?: '/home/i3/Documents/Publii/sites/shyrzlynwkhs/input/db.sqlite';
        $mediaPath = $this->option('media') ?: '/home/i3/Documents/Publii/sites/shyrzlynwkhs/input/media';

        if (! is_file($dbPath)) {
            $this->error("DB not found: {$dbPath}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('post_tag')->delete();
            Post::query()->delete();
            Tag::query()->delete();
            Author::query()->delete();
            $this->warn('Content tables cleared.');
        }

        $pdo = new PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->importAuthors($pdo);
        $this->importTags($pdo);
        $this->importPosts($pdo, $mediaPath);

        $this->info('Import complete.');
        $this->table(
            ['entity', 'count'],
            [
                ['authors', Author::count()],
                ['tags', Tag::count()],
                ['posts', Post::where('type', 'post')->count()],
                ['pages', Post::where('type', 'page')->count()],
            ]
        );

        return self::SUCCESS;
    }

    private function importAuthors(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, name, username, config, additional_data FROM authors')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $slug = Str::slug($row['username'] ?: $row['name']) ?: 'author-'.$row['id'];
            Author::updateOrCreate(
                ['publii_id' => (int) $row['id']],
                [
                    'name' => trim($row['name'] ?? '') ?: 'نویسنده',
                    'username' => $row['username'] ?? null,
                    'slug' => $slug,
                    'bio' => null,
                ]
            );
        }
        $this->info('Authors: '.count($rows));
    }

    private function importTags(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, name, slug, description FROM tags')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $slug = trim((string) ($row['slug'] ?? '')) ?: Str::slug($row['name']);
            Tag::updateOrCreate(
                ['publii_id' => (int) $row['id']],
                [
                    'name' => $row['name'],
                    'slug' => $slug,
                    'description' => $row['description'] ?? null,
                ]
            );
        }
        $this->info('Tags: '.count($rows));
    }

    private function importPosts(PDO $pdo, string $mediaPath): void
    {
        $posts = $pdo->query('SELECT * FROM posts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $coreStmt = $pdo->prepare("SELECT value FROM posts_additional_data WHERE post_id = ? AND key = '_core' LIMIT 1");
        $imgStmt = $pdo->prepare('SELECT url FROM posts_images WHERE id = ? LIMIT 1');
        $tagStmt = $pdo->prepare('SELECT tag_id FROM posts_tags WHERE post_id = ?');

        $publicMedia = public_path('media');
        File::ensureDirectoryExists($publicMedia);

        $count = 0;
        foreach ($posts as $row) {
            $statusRaw = (string) ($row['status'] ?? '');
            if (str_contains($statusRaw, 'trashed')) {
                continue;
            }

            $isPage = str_contains($statusRaw, 'is-page');
            $status = str_contains($statusRaw, 'published') ? 'published' : 'draft';
            if (str_contains($statusRaw, 'hidden')) {
                $status = 'hidden';
            }

            $authorId = null;
            $authorsField = trim((string) ($row['authors'] ?? ''));
            if ($authorsField !== '' && ctype_digit($authorsField)) {
                $authorId = Author::where('publii_id', (int) $authorsField)->value('id');
            }

            $featuredImage = null;
            $fid = (int) ($row['featured_image_id'] ?? 0);
            if ($fid > 0) {
                $imgStmt->execute([$fid]);
                $imgUrl = $imgStmt->fetchColumn();
                if ($imgUrl) {
                    $featuredImage = $this->copyFeaturedImage((int) $row['id'], (string) $imgUrl, $mediaPath, $publicMedia);
                }
            }

            $metaTitle = null;
            $metaDesc = null;
            $coreStmt->execute([(int) $row['id']]);
            $coreJson = $coreStmt->fetchColumn();
            if ($coreJson) {
                $core = json_decode((string) $coreJson, true) ?: [];
                $metaTitle = $core['metaTitle'] ?? null;
                $metaDesc = $core['metaDesc'] ?? null;
            }

            $body = $this->convertBody((string) ($row['text'] ?? ''), (int) $row['id']);
            $excerpt = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? ''), 220);

            $createdMs = (int) ($row['created_at'] ?? 0);
            $publishedAt = $createdMs > 0 ? date('Y-m-d H:i:s', (int) floor($createdMs / 1000)) : now();

            $post = Post::updateOrCreate(
                ['publii_id' => (int) $row['id']],
                [
                    'author_id' => $authorId,
                    'title' => trim((string) $row['title']) ?: 'بدون عنوان',
                    'slug' => $this->uniqueSlug((string) $row['slug'], (int) $row['id']),
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'featured_image' => $featuredImage,
                    'meta_title' => $metaTitle ?: null,
                    'meta_description' => $metaDesc ?: null,
                    'type' => $isPage ? 'page' : 'post',
                    'status' => $status,
                    'featured' => str_contains($statusRaw, 'featured'),
                    'exclude_homepage' => str_contains($statusRaw, 'excluded_homepage'),
                    'published_at' => $publishedAt,
                ]
            );

            $tagStmt->execute([(int) $row['id']]);
            $tagIds = [];
            foreach ($tagStmt->fetchAll(PDO::FETCH_COLUMN) as $publiiTagId) {
                $tid = Tag::where('publii_id', (int) $publiiTagId)->value('id');
                if ($tid) {
                    $tagIds[] = $tid;
                }
            }
            $post->tags()->sync($tagIds);
            $count++;
        }
        $this->info("Posts/pages imported: {$count}");
    }

    private function uniqueSlug(string $slug, int $publiiId): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return 'item-'.$publiiId;
        }
        // Keep Publii slug as-is (may include non-ASCII)
        return $slug;
    }

    private function convertBody(string $text, int $postId): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // TinyMCE HTML already
        if (str_starts_with($text, '<') && ! str_starts_with($text, '[{')) {
            return $this->rewriteDomainMedia($text, $postId);
        }

        // Publii block editor JSON
        if (str_starts_with($text, '[')) {
            $blocks = json_decode($text, true);
            if (is_array($blocks)) {
                return $this->rewriteDomainMedia($this->blocksToHtml($blocks), $postId);
            }
        }

        return $this->rewriteDomainMedia(nl2br(e($text)), $postId);
    }

    private function blocksToHtml(array $blocks): string
    {
        $html = [];
        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            $content = $block['content'] ?? '';
            $config = $block['config'] ?? [];

            if (is_array($content) && isset($content['text'])) {
                $content = $content['text'];
            }
            if (is_array($content) && isset($content['toc'])) {
                $content = '<ul class="post-toc">'.$content['toc'].'</ul>';
            }
            if (! is_string($content)) {
                $content = '';
            }

            $html[] = match ($type) {
                'publii-header' => $this->headerBlock($content, $config),
                'publii-paragraph' => '<p>'.$content.'</p>',
                'publii-quote' => '<blockquote>'.$content.'</blockquote>',
                'publii-code' => '<pre><code>'.e(strip_tags($content)).'</code></pre>',
                'publii-list' => $content,
                'publii-html' => $content,
                'publii-separator' => '<hr>',
                'publii-toc' => $content,
                'publii-image' => $this->imageBlock($block),
                default => $content !== '' ? '<div class="block">'.$content.'</div>' : '',
            };
        }

        return implode("\n", array_filter($html));
    }

    private function headerBlock(string $content, array $config): string
    {
        $level = (int) ($config['headingLevel'] ?? 2);
        $level = max(1, min(6, $level));

        return "<h{$level}>{$content}</h{$level}>";
    }

    private function imageBlock(array $block): string
    {
        $url = $block['content']['url'] ?? $block['url'] ?? '';
        $alt = $block['content']['alt'] ?? $block['config']['alt'] ?? '';
        if (! $url) {
            return '';
        }

        return '<p class="post-image"><img src="'.e($url).'" alt="'.e($alt).'" loading="lazy"></p>';
    }

    private function rewriteDomainMedia(string $html, int $postId): string
    {
        $html = str_replace('#DOMAIN_NAME#', '/media/posts/'.$postId.'/', $html);
        // map absolute sudoshz media to local public media when present
        $html = preg_replace(
            '#https?://sudoshz\.ir/media/#',
            '/media/',
            $html
        ) ?? $html;

        return $html;
    }

    private function copyFeaturedImage(int $postId, string $url, string $mediaPath, string $publicMedia): ?string
    {
        // url often like "cover.jpg" relative to posts/{id}/
        $basename = basename(parse_url($url, PHP_URL_PATH) ?: $url);
        $src = rtrim($mediaPath, '/').'/posts/'.$postId.'/'.$basename;
        if (! is_file($src)) {
            // try as path fragment
            $alt = rtrim($mediaPath, '/').'/posts/'.$postId.'/'.ltrim($url, '/');
            if (is_file($alt)) {
                $src = $alt;
                $basename = basename($alt);
            } else {
                return '/media/posts/'.$postId.'/'.$basename;
            }
        }

        $destDir = $publicMedia.'/posts/'.$postId;
        File::ensureDirectoryExists($destDir);
        $dest = $destDir.'/'.$basename;
        if (! is_file($dest)) {
            @copy($src, $dest);
        }

        return '/media/posts/'.$postId.'/'.$basename;
    }
}
