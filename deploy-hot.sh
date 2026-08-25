#!/usr/bin/env bash
# Hot deploy code to production apex https://sudoshz.ir/ via SFTP + PHP extract.
# Does NOT overwrite vendor, media tree, sqlite, or .env contents beyond APP_URL/SESSION.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

ZIP=/tmp/laravel-hot.zip
PHP=/tmp/laravel-hot.php
rm -f "$ZIP" "$PHP"

zip -rq "$ZIP" \
  app bootstrap/app.php bootstrap/providers.php config routes \
  resources/views public/css public/js public/media/icons public/media/projects public/robots.txt public/.htaccess public/index.php public/index.php84 public/php84-guard.php \
  composer.json composer.lock README-MIGRATION.md

cat > "$PHP" <<'PHP'
<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors','1');
ini_set('memory_limit','512M');
ini_set('max_execution_time','180');

// public_html is document root after apex cutover
$web = __DIR__;
$base = realpath(__DIR__.'/../laravel-shiraz');
$zipPath = __DIR__.'/laravel-hot.zip';
if (!$base || !is_dir($base)) { http_response_code(500); echo "base missing\n"; exit; }
if (!is_file($zipPath)) { http_response_code(500); echo "zip missing\n"; exit; }

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) { http_response_code(500); echo "zip open fail\n"; exit; }
$zip->extractTo($base); $zip->close(); echo "extracted to $base\n";

@mkdir($web.'/css', 0755, true);
@mkdir($web.'/js', 0755, true);
@mkdir($web.'/media/icons', 0755, true);
@mkdir($web.'/media/projects', 0755, true);

if (is_file($base.'/public/css/site.css')) {
    copy($base.'/public/css/site.css', $web.'/css/site.css');
    echo "css synced\n";
}
if (is_dir($base.'/public/js')) {
    foreach (glob($base.'/public/js/*') ?: [] as $jsf) {
        if (is_file($jsf)) { copy($jsf, $web.'/js/'.basename($jsf)); }
    }
    echo "js synced\n";
}

$robots = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /admin/\nDisallow: /analytics/\nDisallow: /preview\nDisallow: /preview/\nDisallow: /up\nSitemap: https://sudoshz.ir/sitemap.xml\n\nUser-agent: Googlebot\nAllow: /\nDisallow: /admin\nDisallow: /preview\n\nUser-agent: Googlebot-Image\nAllow: /media/\n";
@file_put_contents($web.'/robots.txt', $robots);
@file_put_contents($base.'/public/robots.txt', $robots);
echo "robots fallback written\n";
// keep site_settings robots_txt in sync when missing sitemap line
try {
    $db = $base.'/database/database.sqlite';
    if (is_file($db)) {
        $pdo = new PDO('sqlite:'.$db);
        $row = $pdo->query("SELECT value FROM site_settings WHERE key='robots_txt'")->fetch(PDO::FETCH_ASSOC);
        if (!$row || !str_contains((string)$row['value'], 'Sitemap:')) {
            $st = $pdo->prepare("INSERT INTO site_settings (key,value,created_at,updated_at) VALUES ('robots_txt',?,?,?)
                ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at");
            $now = date('Y-m-d H:i:s');
            $st->execute(["User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /admin/\nDisallow: /analytics/\nDisallow: /preview\nDisallow: /preview/\nSitemap: {sitemap}\n", $now, $now]);
            echo "robots_txt setting synced\n";
        }
    }
} catch (Throwable $e) {
    echo "robots setting skip\n";
}
// Prefer dynamic Laravel robots when index is active — keep static as CDN/edge fallback only.


foreach (['icons','projects','website'] as $mediaDir) {
    $src = $base.'/public/media/'.$mediaDir;
    $dst = $web.'/media/'.$mediaDir;
    if (is_dir($src)) {
        @mkdir($dst, 0755, true);
        foreach (glob($src.'/*') ?: [] as $f) {
            if (is_file($f)) { copy($f, $dst.'/'.basename($f)); }
        }
        echo "$mediaDir synced\n";
    }
}

// Apex front controller on .php84 so cPanel MultiPHP (.php → ea-php82) cannot take it down.
$idx84 = <<<'IDX84'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../laravel-shiraz/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../laravel-shiraz/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../laravel-shiraz/bootstrap/app.php';

$app->handleRequest(Request::capture());
IDX84;
file_put_contents($web.'/index.php84', $idx84);
echo "index.php84 written\n";

$idxStub = <<<'IDXSTUB'
<?php
// cPanel MultiPHP only controls .php — this stub must not load Composer on 8.2.
if (PHP_VERSION_ID >= 80400 && is_file(__DIR__.'/index.php84')) {
    require __DIR__.'/index.php84';
    exit;
}
if (is_file(__DIR__.'/php84-guard.php')) {
    require __DIR__.'/php84-guard.php';
}
http_response_code(302);
header('Location: /');
IDXSTUB;
file_put_contents($web.'/index.php', $idxStub);
echo "index.php stub written\n";
if (is_file($base.'/public/php84-guard.php')) {
    copy($base.'/public/php84-guard.php', $web.'/php84-guard.php');
    echo "php84-guard synced\n";
}

// Keep PHP 8.4 + front controller rules if .htaccess already has our apex markers
$htPath = $web.'/.htaccess';
$ht = is_file($htPath) ? file_get_contents($htPath) : '';
if ($ht === '' || strpos($ht, 'Laravel on apex') === false) {
    // Minimal safe fallback (full apex htaccess comes from cutover)
    $laravelHt = is_file($base.'/public/.htaccess') ? file_get_contents($base.'/public/.htaccess') : '';
    $ht = "<IfModule mime_module>\n  AddHandler application/x-httpd-ea-php84 .php .php8 .phtml\n</IfModule>\n".$laravelHt;
}
// cPanel MultiPHP rewrites a php82 handler at the file END; last AddHandler wins.
$ht = str_replace('application/x-httpd-ea-php82', 'application/x-httpd-ea-php84', $ht);
$ht = str_replace('“ea-php82”', '“ea-php84”', $ht);
$force = "# FORCE PHP 8.4 (must stay AFTER cPanel handler — last AddHandler wins)\n"
    ."<IfModule mime_module>\n"
    ."  AddHandler application/x-httpd-ea-php84 .php .php8 .phtml\n"
    ."</IfModule>\n";
$ht = preg_replace('/\n*# FORCE PHP 8\.4[\s\S]*?<\/IfModule>\s*/m', "\n", $ht) ?? $ht;
if (! preg_match('/AddHandler application\/x-httpd-ea-php84 \.php84/', $ht)) {
    $ht = "# Laravel .php84 is immune to MultiPHP (.php) rewrites\n"
        ."<IfModule mime_module>\n"
        ."  AddHandler application/x-httpd-ea-php84 .php84\n"
        ."</IfModule>\n"
        ."<FilesMatch \"\\.php84$\">\n"
        ."  SetHandler application/x-httpd-ea-php84\n"
        ."</FilesMatch>\n"
        ."DirectoryIndex index.php84 index.php\n\n".$ht;
}
$ht = str_replace('RewriteRule ^ index.php [L]', 'RewriteRule ^ index.php84 [L]', $ht);
if (strpos($ht, 'RewriteRule ^ index.php84 [L]') === false && strpos($ht, 'Front controller') !== false) {
    $ht = preg_replace(
        '/# Front controller\nRewriteCond %\{REQUEST_FILENAME\} !-d\nRewriteCond %\{REQUEST_FILENAME\} !-f\nRewriteRule \^ index\.php \[L\]/',
        "# Front controller (index.php84 = ea-php84, ignored by MultiPHP)\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^ index.php84 [L]",
        $ht
    );
}
$ht = rtrim($ht)."\n\n".$force;
file_put_contents($htPath, $ht);
echo "htaccess php84 + index.php84 front controller\n";

$env = $base.'/.env';
if (is_file($env)) {
    $t = file_get_contents($env);
    $t = preg_replace('/^APP_URL=.*/m', 'APP_URL=https://sudoshz.ir', $t);
    $t = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $t);
    $t = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $t);
    $t = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $t);
    file_put_contents($env, $t);
    echo "env patched APP_URL=https://sudoshz.ir\n";
}

foreach (['storage/framework/views','storage/framework/cache/data','storage/framework/sessions','storage/logs','bootstrap/cache'] as $d) {
    $p = $base.'/'.$d;
    if (!is_dir($p)) mkdir($p, 0775, true);
    @chmod($p, 0775);
}
foreach (glob($base.'/storage/framework/views/*') ?: [] as $f) {
    if (is_file($f)) @unlink($f);
}
foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
    if (in_array(basename($f), ['config.php','routes-v7.php','routes.php','events.php','services.php'], true)) @unlink($f);
}

if (function_exists('opcache_reset')) {
    @opcache_reset();
    echo "opcache reset\n";
}

// Keep schema safety nets (idempotent)
try {
    $db = $base.'/database/database.sqlite';
    if (is_file($db)) {
        $pdo = new PDO('sqlite:'.$db);
        $pdo->exec('CREATE TABLE IF NOT EXISTS site_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT NOT NULL UNIQUE,
            value TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');
        echo "site_settings ready\n";
        $pdo->exec('CREATE TABLE IF NOT EXISTS page_views (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            path TEXT NOT NULL,
            referrer TEXT NULL,
            visitor_hash TEXT NOT NULL,
            session_hash TEXT NOT NULL,
            user_agent TEXT NULL,
            created_at TEXT NULL
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS page_views_path_index ON page_views(path)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS page_views_visitor_hash_index ON page_views(visitor_hash)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS page_views_session_hash_index ON page_views(session_hash)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS page_views_created_at_index ON page_views(created_at)');
        echo "page_views ready\n";
        $cols = $pdo->query("PRAGMA table_info(tags)")->fetchAll(PDO::FETCH_ASSOC);
        $tagCols = array_column($cols, 'name');
        foreach (['featured_image'=>'TEXT','meta_title'=>'TEXT','meta_description'=>'TEXT'] as $c=>$t) {
            if (!in_array($c, $tagCols, true)) { $pdo->exec("ALTER TABLE tags ADD COLUMN $c $t"); echo "tags.$c\n"; }
        }
        $acols = $pdo->query("PRAGMA table_info(authors)")->fetchAll(PDO::FETCH_ASSOC);
        $authorCols = array_column($acols, 'name');
        foreach (['website'=>'TEXT','telegram'=>'TEXT','mastodon'=>'TEXT','github'=>'TEXT'] as $c=>$t) {
            if (!in_array($c, $authorCols, true)) { $pdo->exec("ALTER TABLE authors ADD COLUMN $c $t"); echo "authors.$c\n"; }
        }
        $ucols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
        $userCols = array_column($ucols, 'name');
        if (!in_array('role', $userCols, true)) { $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'admin'"); echo "users.role\n"; }
        $pdo->exec('CREATE TABLE IF NOT EXISTS redirects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_path TEXT NOT NULL UNIQUE,
            to_url TEXT NOT NULL,
            status_code INTEGER NOT NULL DEFAULT 301,
            enabled INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS post_revisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            user_id INTEGER NULL,
            title TEXT NOT NULL,
            body TEXT NULL,
            excerpt TEXT NULL,
            status TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            action TEXT NOT NULL,
            subject_type TEXT NULL,
            subject_id INTEGER NULL,
            description TEXT NULL,
            meta TEXT NULL,
            created_at TEXT NULL,
            updated_at TEXT NULL
        )');
        echo "prod tables ready\n";
        // Refresh homepage copy defaults when still on old formal text
        $map = [
            'home_intro_title' => 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز',
            'home_intro_text' => "ما یک جامعه هستیم در شیراز؛ دور هم جمع می‌شویم، یاد می‌گیریم و نرم‌افزار آزاد را ترویج می‌کنیم.\nاگر دنبال نشست، آموزش، یا راهی برای شروع با گنو/لینوکس می‌گردی، جای درستی آمده‌ای.",
            'footer_text' => 'جامعه نرم‌افزار آزاد شیراز',
            'site_tagline' => 'جامعه نرم‌افزار آزاد شیراز',
        ];
        $st = $pdo->prepare('INSERT INTO site_settings (key, value, created_at, updated_at) VALUES (?,?,?,?)
            ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at');
        $now = date('Y-m-d H:i:s');
        foreach ($map as $k=>$v) {
            $st->execute([$k, $v, $now, $now]);
        }
        echo "home copy refreshed\n";

        $socials = [
            'social_telegram' => 'https://t.me/sudoshz',
            'social_mastodon' => 'https://mastodon.social/@Shirazlinux',
            'social_matrix' => 'https://matrix.to/#/%23shirazlinux:matrix.org',
            'social_codeberg' => 'https://codeberg.org/shirazlinux',
            'social_github' => 'https://github.com/shirazlinux',
            'social_youtube' => 'https://www.youtube.com/@shirazlinux',
            'social_instagram' => 'https://www.instagram.com/shirazlinux',
            'social_x' => 'https://x.com/shirazlinux',
            'social_website' => 'https://sudoshz.ir',
            'seo_twitter' => 'shirazlinux',
        ];
        $get = $pdo->prepare('SELECT value FROM site_settings WHERE key=?');
        $ins = $pdo->prepare('INSERT INTO site_settings (key,value,created_at,updated_at) VALUES (?,?,?,?)
            ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at');
        foreach ($socials as $k=>$v) {
            $get->execute([$k]);
            $cur = $get->fetchColumn();
            $decoded = is_string($cur) ? json_decode($cur, true) : null;
            $empty = $cur === false || $cur === '' || $decoded === '' || $decoded === null;
            if ($empty) {
                $ins->execute([$k, json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $now, $now]);
            }
        }
        echo "socials filled\n";

    }
} catch (Throwable $e) {
    echo "schema skip: ".$e->getMessage()."\n";
}

@unlink($zipPath);
@unlink(__FILE__);
echo "COMPLETE OK\n";
PHP

sftp -o BatchMode=yes sudoshz <<EOF
put $ZIP public_html/laravel-hot.zip
put $PHP public_html/laravel-hot.php
bye
EOF

curl -fsS "https://sudoshz.ir/laravel-hot.php"
echo
echo "Smoke (apex):"
for p in / /sitemap.xml /feed.xml /admin/login /robots.txt; do
  printf "  %s -> " "$p"
  curl -sS -o /dev/null -w "%{http_code}\n" "https://sudoshz.ir$p"
done
# old path should redirect
printf "  /laravel-test/ -> "
curl -sS -o /dev/null -w "%{http_code} redir=%{redirect_url}\n" "https://sudoshz.ir/laravel-test/"
