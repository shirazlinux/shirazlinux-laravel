#!/usr/bin/env bash
# One-shot: push latest Laravel code + run apex cutover on sudoshz.ir
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

ZIP=/tmp/laravel-cutover.zip
CUTOVER_PHP="$ROOT/deploy-cutover-root.php"
rm -f "$ZIP"

echo "== packing code =="
zip -rq "$ZIP" \
  app bootstrap/app.php bootstrap/providers.php config routes \
  resources/views public/css public/js public/media/icons public/media/projects public/robots.txt public/.htaccess public/index.php \
  composer.json composer.lock README-MIGRATION.md

echo "== uploading to server (via laravel-test path, still active pre-cutover) =="
sftp -o BatchMode=yes sudoshz <<EOF
put $ZIP public_html/laravel-test/laravel-hot.zip
put $CUTOVER_PHP public_html/laravel-test/cutover-root.php
bye
EOF

echo "== running cutover =="
curl -fsS --max-time 300 "https://sudoshz.ir/laravel-test/cutover-root.php"
echo
echo
echo "== smoke tests =="
for p in / /sitemap.xml /feed.xml /admin/login /robots.txt /tags /about; do
  code=$(curl -sS -o /tmp/apex-smoke.html -w "%{http_code}" "https://sudoshz.ir$p" || echo err)
  title=$(grep -oE '<title>[^<]*' /tmp/apex-smoke.html 2>/dev/null | head -1 | sed 's/<title>//')
  printf "  %s -> %s %s\n" "$p" "$code" "${title:0:60}"
done

echo "== redirects =="
for p in /laravel-test/ /laravel-test/admin/login /about/; do
  printf "  %s -> " "$p"
  curl -sS -o /dev/null -w "%{http_code} → %{redirect_url}\n" "https://sudoshz.ir$p"
done

echo "== media sample =="
# logo from homepage
logo=$(grep -oE 'src="https://sudoshz.ir/[^"]*logo[^"]*"' /tmp/apex-smoke.html 2>/dev/null | head -1 || true)
echo "  $logo"
if [[ -n "${logo:-}" ]]; then
  url=$(echo "$logo" | sed 's/src="//;s/"$//')
  printf "  logo fetch -> "
  curl -sS -o /dev/null -w "%{http_code}\n" "$url" || true
fi

echo "== sub-apps still physical =="
for p in /donate/ /free/; do
  printf "  %s -> " "$p"
  curl -sS -o /dev/null -w "%{http_code}\n" "https://sudoshz.ir$p" || true
done

echo "DONE"
