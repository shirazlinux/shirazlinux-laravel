# شیرازلینوکس — نسخه production (Laravel)

سایت رسمی جامعه شیرازلینوکس روی `https://sudoshz.ir/` (جایگزین کامل Publii).

## آدرس production
- سایت: https://sudoshz.ir/
- ادمین: https://sudoshz.ir/admin/login
- مسیر قدیمی `/laravel-test/*` با ۳۰۱ به ریشه منتقل می‌شود.

## مسیرها روی سرور
- اپ: `/home/sudoshzi/laravel-shiraz/`
- وب (apex): `/home/sudoshzi/public_html/` → `index.php` به `laravel-shiraz` وصل است
- مدیا وب: `/home/sudoshzi/public_html/media/`
- DB: SQLite در `laravel-shiraz/database/database.sqlite`
- PHP: `ea-php84` در `public_html/.htaccess`
- بکاپ استاتیک Publii: `~/backups/main-sudoshz-*` (دیگر سرو نمی‌شود)

## ساب‌اپ‌های فیزیکی (دست‌نخورده)
`donate`, `free`, `gnulinux`, `hcc`, `ilovefs`, `nadan`, `note`, `read`, `shirazmetro`, `terminal`, `devops`, …

## Deploy
```bash
cd /home/i3/Documents/shirazlinux-laravel
./deploy-hot.sh
```

یک‌بار cutover (از قبل انجام شده):
```bash
./deploy-cutover.sh   # فقط اگر لازم شد دوباره apex را از روی /laravel-test بسازید
```

## توسعه محلی
```bash
cd /home/i3/Documents/shirazlinux-laravel
php artisan serve
```
