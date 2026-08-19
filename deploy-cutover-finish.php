<?php
/**
 * Finish/repair apex cutover after partial run (no symlink required).
 * Upload to public_html/laravel-test/cutover-finish.php and open once.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '600');

function out(string $m): void
{
    echo $m, "\n";
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
        $rel = substr($item->getPathname(), strlen(rtrim($src, '/')) + 1);
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
    $publicHtml = realpath(__DIR__.'/..');
    if (basename((string) $publicHtml) !== 'public_html') {
        throw new RuntimeException('expected under public_html/laravel-test, got '.__DIR__);
    }
    $home = dirname($publicHtml);
    $base = realpath($home.'/laravel-shiraz');
    if (! $base) {
        throw new RuntimeException('laravel-shiraz missing');
    }

    out('=== finish apex cutover ===');
    out('publicHtml='.$publicHtml);
    out('base='.$base);

    // Extract zip if present
    foreach ([$publicHtml.'/laravel-hot.zip', __DIR__.'/laravel-hot.zip'] as $zipPath) {
        if (is_file($zipPath)) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($base);
                $zip->close();
                out('extracted '.basename($zipPath));
                @unlink($zipPath);
            }
            break;
        }
    }

    $apexMedia = $publicHtml.'/media';
    $appMedia = $base.'/public/media';

    // Restore app media directory (no symlink — host disables it)
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
            out('WARN: empty app media created');
        }
    } else {
        out('app media present');
    }

    // Ensure apex media has content (web-facing)
    if (! is_dir($apexMedia)) {
        if (is_dir($appMedia)) {
            $n = copyTreeFiles($appMedia, $apexMedia);
            out("created apex media from app ($n files)");
        } else {
            ensureDir($apexMedia);
        }
    } else {
        // Merge any missing pieces from app → apex (icons/projects updates)
        if (is_dir($appMedia)) {
            foreach (['icons', 'projects'] as $sub) {
                $src = $appMedia.'/'.$sub;
                if (is_dir($src)) {
                    $n = copyTreeFiles($src, $apexMedia.'/'.$sub);
                    if ($n) {
                        out("synced media/$sub ($n files)");
                    }
                }
            }
        }
        out('apex media present');
    }

    // Also keep a physical media under laravel-test for any leftover absolute URLs
    // until 301s fully replace them (copy tree is heavy — only if missing)
    $ltMedia = $publicHtml.'/laravel-test/media';
    if (! is_dir($ltMedia) && is_dir($apexMedia)) {
        // Prefer a lightweight approach: leave missing; apex 301 handles /laravel-test/*
        out('laravel-test/media absent (apex 301 will cover)');
    }

    // Sync css/js/microsites to apex web root
    foreach (['css', 'js', 'microsites'] as $dir) {
        $src = $base.'/public/'.$dir;
        if (is_dir($src)) {
            $n = copyTreeFiles($src, $publicHtml.'/'.$dir);
            out("synced $dir ($n files)");
        }
    }
    if (is_file($base.'/public/favicon.ico')) {
        @copy($base.'/public/favicon.ico', $publicHtml.'/favicon.ico');
    }

    // Front controller
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
    writeFile($publicHtml.'/index.php', $index);
    out('wrote public_html/index.php');

    $robots = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /analytics/\nSitemap: https://sudoshz.ir/sitemap.xml\n";
    writeFile($publicHtml.'/robots.txt', $robots);
    @file_put_contents($base.'/public/robots.txt', $robots);
    out('wrote robots.txt');

    // Apex htaccess
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
RewriteRule ^projects/?$ https://sudoshz.ir/libreplanet [R=301,L]
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

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteCond %{HTTP:x-xsrf-token} .
RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

# Trailing slash → bare path (skip real dirs/files)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

# Front controller
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
  AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>
# END security-headers managed
HT;
    // backup current htaccess once more
    if (is_file($publicHtml.'/.htaccess')) {
        @copy($publicHtml.'/.htaccess', $publicHtml.'/.htaccess.bak-finish-'.date('Ymd-His'));
    }
    writeFile($publicHtml.'/.htaccess', $ht);
    out('wrote apex .htaccess');

    // laravel-test trampoline (physical dir still exists)
    ensureDir($publicHtml.'/laravel-test');
    writeFile($publicHtml.'/laravel-test/.htaccess', "RewriteEngine On\nRewriteRule ^(.*)$ https://sudoshz.ir/$1 [R=301,L]\n");
    writeFile($publicHtml.'/laravel-test/index.php', "<?php header('Location: https://sudoshz.ir/', true, 301); exit;\n");
    // remove leftover cutover scripts
    foreach (['cutover-root.php', 'cutover-fix.php', 'cutover-finish.php', 'laravel-hot.zip', 'laravel-hot.php'] as $junk) {
        $p = $publicHtml.'/laravel-test/'.$junk;
        if (is_file($p)) {
            @unlink($p);
        }
    }
    out('laravel-test trampoline + cleanup');

    // env
    $envPath = $base.'/.env';
    if (is_file($envPath)) {
        $t = file_get_contents($envPath);
        $t = preg_replace('/^APP_URL=.*/m', 'APP_URL=https://sudoshz.ir', $t);
        $t = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $t);
        $t = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $t);
        $t = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', $t);
        file_put_contents($envPath, $t);
        out('APP_URL=https://sudoshz.ir');
    }

    // robots setting
    try {
        $db = $base.'/database/database.sqlite';
        if (is_file($db)) {
            $pdo = new PDO('sqlite:'.$db);
            $row = $pdo->query("SELECT value FROM site_settings WHERE key='robots_txt'")->fetch(PDO::FETCH_ASSOC);
            if ($row && is_string($row['value']) && str_contains($row['value'], 'laravel-test')) {
                $val = str_replace(['https://sudoshz.ir/laravel-test/sitemap.xml', '/laravel-test/sitemap.xml'], ['https://sudoshz.ir/sitemap.xml', '/sitemap.xml'], $row['value']);
                $st = $pdo->prepare("UPDATE site_settings SET value=?, updated_at=? WHERE key='robots_txt'");
                $st->execute([$val, date('Y-m-d H:i:s')]);
                out('robots_txt setting updated');
            }
        }
    } catch (Throwable $e) {
        out('db patch skip: '.$e->getMessage());
    }

    // caches
    foreach (['storage/framework/views', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/logs', 'bootstrap/cache'] as $d) {
        ensureDir($base.'/'.$d, 0775);
        @chmod($base.'/'.$d, 0775);
    }
    foreach (glob($base.'/storage/framework/views/*') ?: [] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
    foreach (glob($base.'/bootstrap/cache/*.php') ?: [] as $f) {
        if (in_array(basename($f), ['config.php', 'routes-v7.php', 'routes.php', 'events.php', 'services.php'], true)) {
            @unlink($f);
        }
    }
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        out('opcache reset');
    }

    out('=== FINISH COMPLETE ===');
    out('Site: https://sudoshz.ir/');
    out('Admin: https://sudoshz.ir/admin/login');
} catch (Throwable $e) {
    out('FAIL: '.$e->getMessage());
    out($e->getFile().':'.$e->getLine());
}
