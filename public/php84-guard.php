<?php
/**
 * cPanel MultiPHP nightly rewrites AddHandler to ea-php82 at EOF.
 * This file has no Composer deps so it still runs on 8.2 and restores 8.4.
 */
function sl_force_php84_htaccess(string $htPath): bool
{
    if (! is_file($htPath) || ! is_writable($htPath)) {
        return false;
    }
    $ht = file_get_contents($htPath);
    if ($ht === false) {
        return false;
    }
    $ht = str_replace('application/x-httpd-ea-php82', 'application/x-httpd-ea-php84', $ht);
    $ht = str_replace('“ea-php82”', '“ea-php84”', $ht);
    $ht = preg_replace('/\n*# FORCE PHP 8\.4[\s\S]*?<\/IfModule>\s*/m', "\n", $ht) ?? $ht;
    $force = "\n# FORCE PHP 8.4 (must stay AFTER cPanel handler — last AddHandler wins)\n"
        ."<IfModule mime_module>\n"
        ."  AddHandler application/x-httpd-ea-php84 .php .php8 .phtml\n"
        ."</IfModule>\n";
    $ht = rtrim($ht)."\n".$force;
    $ok = file_put_contents($htPath, $ht) !== false;
    @chmod($htPath, 0644);

    return $ok;
}

$direct = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'php84-guard.php';

if (PHP_VERSION_ID >= 80400) {
    if ($direct) {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'PHP '.PHP_VERSION." OK\n";
        exit;
    }

    return;
}

$fixed = sl_force_php84_htaccess(__DIR__.'/.htaccess');

if ($direct) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $fixed ? "php84 restored; Apache will use it on the next request\n" : "htaccess write failed\n";
    exit;
}

$cookie = 'sl_php84_bounce';
if (! empty($_COOKIE[$cookie])) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>سایت موقتاً در دسترس نیست</title>'
        .'<body style="font-family:Tahoma,sans-serif;padding:2rem;text-align:center">'
        .'<h1>سایت موقتاً قطع است</h1>'
        .'<p>هاست PHP را روی ۸.۲ برگردانده. در cPanel از MultiPHP Manager نسخهٔ <b>ea-php84</b> را انتخاب کنید.</p>'
        .'</body></html>';
    exit;
}

header('Set-Cookie: '.$cookie.'=1; Max-Age=60; Path=/; HttpOnly; SameSite=Lax');
header('Cache-Control: no-store');
http_response_code(302);
header('Location: '.($_SERVER['REQUEST_URI'] ?? '/'));
echo '<!DOCTYPE html><html lang="fa" dir="rtl"><meta charset="utf-8">'
    .'<meta http-equiv="refresh" content="0">'
    .'<title>در حال اتصال</title><p>در حال اتصال مجدد…</p></html>';
exit;
