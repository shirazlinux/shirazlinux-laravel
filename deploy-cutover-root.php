<?php
/**
 * One-shot cutover: serve Laravel at https://sudoshz.ir/ (apex)
 * instead of static main-sudoshz + /laravel-test.
 *
 * Safe-ish: moves static site to ~/backups/, keeps sub-apps (donate, free, …),
 * redirects /laravel-test/* → /*.
 *
 * Run once via: https://sudoshz.ir/laravel-test/cutover-root.php
 * (placed next to laravel-test so it is not rewritten to main-sudoshz)
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '300');

// Expected upload path: public_html/laravel-test/cutover-root.php
// (must be under laravel-test so pre-cutover htaccess does not rewrite to main-sudoshz)
$dir = realpath(__DIR__);
if (is_dir($dir.'/laravel-shiraz') && is_dir($dir.'/public_html')) {
    // invoked from home (CLI)
    $home = $dir;
    $publicHtml = $dir.'/public_html';
} elseif (basename($dir) === 'laravel-test' && basename(dirname($dir)) === 'public_html') {
    $publicHtml = dirname($dir);
    $home = dirname($publicHtml);
} elseif (basename($dir) === 'public_html') {
    $publicHtml = $dir;
    $home = dirname($publicHtml);
} else {
    http_response_code(500);
    echo "Cannot resolve public_html from ".__DIR__."\n";
    exit(1);
}
$base = realpath($home.'/laravel-shiraz');
$webRoot = $publicHtml;
$oldWeb = $publicHtml.'/laravel-test';
$staticDir = $publicHtml.'/main-sudoshz';
$backupRoot = $home.'/backups';
$stamp = date('Ymd-His');

function out(string $m): void
{
    echo $m."\n";
    @ob_flush();
    @flush();
}

function ensureDir(string $p, int $mode = 0755): void
{
    if (! is_dir($p)) {
        if (! @mkdir($p, $mode, true) && ! is_dir($p)) {
            throw new RuntimeException("mkdir failed: $p");
        }
    }
}

function writeFile(string $path, string $contents): void
{
    ensureDir(dirname($path));
    if (@file_put_contents($path, $contents) === false) {
        throw new RuntimeException("write failed: $path");
    }
}

function copyTreeFiles(string $src, string $dst): int
{
    if (! is_dir($src)) {
        return 0;
    }
    ensureDir($dst);
    $n = 0;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($src) + 1);
        $target = $dst.'/'.$rel;
        if ($item->isDir()) {
            ensureDir($target);
        } else {
            ensureDir(dirname($target));
            if (@copy($item->getPathname(), $target)) {
                $n++;
            }
        }
    }

    return $n;
}

try {
    out("=== ShirazLinux Laravel apex cutover ===");
    out('time: '.date('c'));
    out("home=$home");
    out("publicHtml=$publicHtml");
    out("laravel=$base");

    if (! $base || ! is_dir($base)) {
        throw new RuntimeException('laravel-shiraz missing');
    }
    if (! is_dir($publicHtml)) {
        throw new RuntimeException('public_html missing');
    }

    // 0) Optional: extract latest code zip uploaded next to this script or in public_html
    foreach ([$publicHtml.'/laravel-hot.zip', $oldWeb.'/laravel-hot.zip', $publicHtml.'/laravel-cutover.zip'] as $zipPath) {
        if (is_file($zipPath)) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($base);
                $zip->close();
                out('extracted code zip: '.basename($zipPath).' → '.$base);
                @unlink($zipPath);
            } else {
                out('WARN: could not open zip '.$zipPath);
            }
            break;
        }
    }

    // 1) Backup root .htaccess
    $htSrc = $publicHtml.'/.htaccess';
    if (is_file($htSrc)) {
        $htBak = $publicHtml.'/.htaccess.bak-pre-laravel-'.$stamp;
        @copy($htSrc, $htBak);
        out("backed up .htaccess → ".basename($htBak));
    }

    // 2) Move static Publii site out of web root (keep backup)
    ensureDir($backupRoot);
    if (is_dir($staticDir)) {
        $dest = $backupRoot.'/main-sudoshz-'.$stamp;
        if (@rename($staticDir, $dest)) {
            out("moved main-sudoshz → $dest");
        } else {
            // fallback copy+leave (disk heavy) — prefer rename failure note
            out('WARN: rename main-sudoshz failed; leaving in place but it will no longer be served');
        }
    } else {
        out('main-sudoshz already absent (ok)');
    }

    // 3) Media: ensure public_html/media has the live tree
    $mediaTargets = [
        $oldWeb.'/media',
        $base.'/public/media',
    ];
    $apexMedia = $webRoot.'/media';
    if (! is_dir($apexMedia)) {
        $moved = false;
        foreach ($mediaTargets as $src) {
            if (is_dir($src)) {
                if (@rename($src, $apexMedia)) {
                    out("moved media from $src → public_html/media");
                    $moved = true;
                    break;
                }
            }
        }
        if (! $moved) {
            // copy from best available
            foreach ($mediaTargets as $src) {
                if (is_dir($src)) {
                    $n = copyTreeFiles($src, $apexMedia);
                    out("copied media from $src ($n files)");
                    $moved = true;
                    break;
                }
            }
        }
        if (! $moved) {
            ensureDir($apexMedia);
            out('WARN: created empty public_html/media');
        }
    } else {
        out('public_html/media already exists');
    }

    // Keep app public/media as a real directory (host disables symlink()).
    // Dual-write via MediaStorage covers public_html + app public after cutover.
    $appMedia = $base.'/public/media';
    if (! is_dir($appMedia)) {
        $baks = glob($base.'/public/media.bak-*') ?: [];
        rsort($baks);
        if ($baks && is_dir($baks[0])) {
            if (@rename($baks[0], $appMedia)) {
                out('restored app media from '.basename($baks[0]));
            } else {
                $n = copyTreeFiles($baks[0], $appMedia);
                out("copied app media from bak ($n files)");
            }
        } elseif (is_dir($apexMedia)) {
            $n = copyTreeFiles($apexMedia, $appMedia);
            out("seeded app media from apex ($n files)");
        } else {
            ensureDir($appMedia);
            out('created empty app media');
        }
    } else {
        // Ensure icons/projects from package land on apex too
        foreach (['icons', 'projects'] as $sub) {
            $src = $appMedia.'/'.$sub;
            if (is_dir($src) && is_dir($apexMedia)) {
                $n = copyTreeFiles($src, $apexMedia.'/'.$sub);
                if ($n) {
                    out("synced media/$sub to apex ($n files)");
                }
            }
        }
        out('app media present');
    }

    // 4) Sync static assets (css/js/icons/projects/microsites)
    foreach (['css', 'js', 'microsites'] as $dir) {
        $src = $base.'/public/'.$dir;
        if (! is_dir($src) && is_dir($oldWeb.'/'.$dir)) {
            $src = $oldWeb.'/'.$dir;
        }
        if (is_dir($src)) {
            $n = copyTreeFiles($src, $webRoot.'/'.$dir);
            out("synced $dir ($n files)");
        }
    }
    // also pull icons/projects from base public/media if present (already under media/)
    if (is_file($base.'/public/favicon.ico')) {
        @copy($base.'/public/favicon.ico', $webRoot.'/favicon.ico');
    }

    // 5) Front controller at apex
    $index = <<<'PHP'
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
PHP;
    writeFile($webRoot.'/index.php', $index);
    out('wrote public_html/index.php');

    // 6) robots fallback
    $robots = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /analytics/\nSitemap: https://sudoshz.ir/sitemap.xml\n";
    writeFile($webRoot.'/robots.txt', $robots);
    @file_put_contents($base.'/public/robots.txt', $robots);
    out('wrote robots.txt');

    // 7) Root .htaccess — Laravel + legacy SEO + sub-apps + PHP 8.4
    $ht = <<<'HT'
# BEGIN cPanel-generated php ini directives, do not edit
<IfModule php7_module>
   php_value error_log "/home/sudoshzi/logs/php.error.log"
   php_flag log_errors On
</IfModule>
<IfModule lsapi_module>
   php_value error_log "/home/sudoshzi/logs/php.error.log"
   php_flag log_errors On
</IfModule>
# END cPanel-generated php ini directives

# Laravel on apex (replaces static main-sudoshz). Sub-apps keep physical dirs.

<IfModule mime_module>
  AddHandler application/x-httpd-ea-php84 .php .php8 .phtml
</IfModule>

Options -MultiViews -Indexes
DirectorySlash Off
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} !=on
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# www → apex
RewriteCond %{HTTP_HOST} ^www\.sudoshz\.ir$ [NC]
RewriteRule ^(.*)$ https://sudoshz.ir/$1 [R=301,L]

# Old test deploy → apex
RewriteRule ^laravel-test(?:/(.*))?$ https://sudoshz.ir/$1 [R=301,L]

# Hide any residual internal publish path
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /+main-sudoshz(/?[^\ ]*) [NC]
RewriteRule ^ https://sudoshz.ir%1 [R=301,L]

# Legacy SEO redirects
RewriteRule ^projects/?$ https://sudoshz.ir/libreplanet/ [R=301,L]
RewriteCond %{HTTP_HOST} ^(www\.)?sudoshz\.ir$ [NC]
RewriteRule ^event(/.*)?$ https://event.sudoshz.ir$1 [R=301,L]
Redirect 301 /fsf-history-redirect https://sudoshz.ir/free-software-history
Redirect 301 /devops-conf-2023 https://devops.sudoshz.ir/
Redirect 301 /gnulinux-conf https://gnulinux.sudoshz.ir/
RedirectMatch 301 (?i)^/items(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/review(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/product(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/wp-admin(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/wp-content(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/wp-includes(/.*)?$ https://sudoshz.ir/
RedirectMatch 301 (?i)^/xmlrpc\.php$ https://sudoshz.ir/

# Authorization headers for API/admin
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteCond %{HTTP:x-xsrf-token} .
RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

# Trailing slash → bare path (Laravel canonical), skip real dirs/files
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

# Front controller (physical files/dirs like /donate /media /css keep serving)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

# BEGIN security-headers managed
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Cross-Origin-Opener-Policy "same-origin-allow-popups"
    Header always set X-XSS-Protection "0"
    Header always unset X-Powered-By
    Header unset X-Powered-By
</IfModule>

<IfModule mod_authz_core.c>
    <FilesMatch "(?i)^(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json|\.user\.ini|php\.ini|\.htpasswd|files\.publii\.json|htaccess\.txt|INDEXNOW-KEY\.txt|config\.php|config\.sample\.php)$">
        Require all denied
    </FilesMatch>
</IfModule>

<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
  AddOutputFilterByType DEFLATE text/javascript application/javascript application/json
  AddOutputFilterByType DEFLATE application/xml application/rss+xml application/atom+xml
  AddOutputFilterByType DEFLATE image/svg+xml font/ttf font/otf application/vnd.ms-fontobject
</IfModule>

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresDefault "access plus 2 days"
  ExpiresByType text/html "access plus 1 hour"
  ExpiresByType text/css "access plus 30 days"
  ExpiresByType application/javascript "access plus 30 days"
  ExpiresByType image/webp "access plus 30 days"
  ExpiresByType image/png "access plus 30 days"
  ExpiresByType image/jpeg "access plus 30 days"
  ExpiresByType image/svg+xml "access plus 30 days"
  ExpiresByType font/woff2 "access plus 180 days"
</IfModule>
# END security-headers managed
HT;
    writeFile($webRoot.'/.htaccess', $ht);
    out('wrote public_html/.htaccess (Laravel apex + PHP 8.4)');

    // 8) laravel-test: keep as 301 trampoline (in case rewrite order differs)
    ensureDir($oldWeb);
    writeFile($oldWeb.'/.htaccess', "RewriteEngine On\nRewriteRule ^(.*)$ https://sudoshz.ir/$1 [R=301,L]\n");
    // minimal index if someone hits directory after rule miss
    writeFile($oldWeb.'/index.php', "<?php header('Location: https://sudoshz.ir/', true, 301); exit;\n");
    out('laravel-test → 301 trampoline');

    // 9) Patch .env APP_URL
    $envPath = $base.'/.env';
    if (is_file($envPath)) {
        $t = file_get_contents($envPath);
        $t = preg_replace('/^APP_URL=.*/m', 'APP_URL=https://sudoshz.ir', $t);
        $t = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $t);
        $t = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $t);
        $t = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $t);
        if (! preg_match('/^APP_URL=/m', $t)) {
            $t .= "\nAPP_URL=https://sudoshz.ir\n";
        }
        file_put_contents($envPath, $t);
        out('patched .env APP_URL=https://sudoshz.ir');
    }

    // 9b) Fix robots_txt setting if it still points at /laravel-test
    try {
        $db = $base.'/database/database.sqlite';
        if (is_file($db)) {
            $pdo = new PDO('sqlite:'.$db);
            $row = $pdo->query("SELECT value FROM site_settings WHERE key='robots_txt'")->fetch(PDO::FETCH_ASSOC);
            if ($row && is_string($row['value']) && str_contains($row['value'], 'laravel-test')) {
                $val = str_replace('https://sudoshz.ir/laravel-test/sitemap.xml', 'https://sudoshz.ir/sitemap.xml', $row['value']);
                $val = str_replace('/laravel-test/sitemap.xml', '/sitemap.xml', $val);
                $st = $pdo->prepare("UPDATE site_settings SET value=?, updated_at=? WHERE key='robots_txt'");
                $st->execute([$val, date('Y-m-d H:i:s')]);
                out('updated site_settings.robots_txt sitemap URL');
            }
            // optional: site_url keys
            foreach (['site_url', 'canonical_base', 'seo_site_url'] as $k) {
                $r = $pdo->query("SELECT value FROM site_settings WHERE key=".$pdo->quote($k))->fetch(PDO::FETCH_ASSOC);
                if ($r && is_string($r['value']) && str_contains($r['value'], 'laravel-test')) {
                    $val = str_replace('https://sudoshz.ir/laravel-test', 'https://sudoshz.ir', $r['value']);
                    $st = $pdo->prepare('UPDATE site_settings SET value=?, updated_at=? WHERE key=?');
                    $st->execute([$val, date('Y-m-d H:i:s'), $k]);
                    out("updated site_settings.$k");
                }
            }
        }
    } catch (Throwable $e) {
        out('settings patch skip: '.$e->getMessage());
    }

    // 10) Clear caches
    foreach (['storage/framework/views', 'storage/framework/cache/data', 'bootstrap/cache'] as $d) {
        $p = $base.'/'.$d;
        ensureDir($p, 0775);
    }
    foreach (glob($base.'/storage/framework/views/*') ?: [] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
    foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
        $bn = basename($f);
        if (in_array($bn, ['config.php', 'routes-v7.php', 'routes.php', 'events.php', 'services.php'], true)) {
            @unlink($f);
        }
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        out('opcache reset');
    }

    // 11) Storage dirs perms
    foreach (['storage/framework/sessions', 'storage/logs', 'storage/framework/cache/data'] as $d) {
        $p = $base.'/'.$d;
        ensureDir($p, 0775);
        @chmod($p, 0775);
    }

    out('=== CUTOVER COMPLETE ===');
    out('Primary site: https://sudoshz.ir/');
    out('Admin: https://sudoshz.ir/admin/login');
    out('Static backup (if moved): '.$backupRoot.'/main-sudoshz-'.$stamp);
} catch (Throwable $e) {
    http_response_code(500);
    out('FAIL: '.$e->getMessage());
    out($e->getTraceAsString());
}
