@php
  $siteName = setting('site_name', 'شیرازلینوکس');
  $defaultTheme = setting('default_theme', 'system');
  $umamiOn = (bool) setting('umami_enabled', true);
  $umamiId = setting('umami_website_id', '5fd9997e-4f84-4028-942d-13783d46dcf6');
  $umamiScript = setting('umami_script_url', 'https://umami.sudoshz.ir/script.js');
  $maintenance = (bool) setting('maintenance_mode', false) && ! auth()->check();
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F1592D" id="meta-theme-color">
    @include('site.partials.seo-head')
    <link rel="shortcut icon" href="{{ asset('media/website/webicon320.png') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('media/website/webicon320.png') }}" type="image/png" sizes="316x316">
    <link rel="apple-touch-icon" href="{{ asset('media/website/webicon320.png') }}">
    <link rel="manifest" href="{{ url('/site.webmanifest') }}">
    {{-- Apply theme before paint to avoid flash --}}
    <script>
    (function () {
      try {
        var k = 'shiraz-theme';
        var saved = localStorage.getItem(k);
        var pref = @json($defaultTheme);
        var theme = saved || (pref === 'light' || pref === 'dark'
          ? pref
          : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
        document.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
      } catch (e) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://umami.sudoshz.ir">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=20260822social-libre">
    <style>
      .logo img{height:48px!important;width:auto!important;max-width:140px!important;max-height:48px!important;object-fit:contain;filter:brightness(0)}
      html[data-theme="dark"] .logo img{filter:brightness(0) invert(1)}
      .home-slider .slider{position:relative;height:min(52vw,405px);min-height:220px;overflow:hidden;background:#1c1917}
      .home-slider .slide{position:absolute!important;inset:0;opacity:0;z-index:1}
      .home-slider .slide.is-active{opacity:1;z-index:2}
      .home-slider .slide img{width:100%!important;height:100%!important;object-fit:cover;display:block}
      .maint-banner{background:#1c1917;color:#fde68a;padding:.85rem 1rem;text-align:center;font-weight:700}
    </style>
    @stack('head')
    @if($umamiOn && $umamiId && $umamiScript)
    <script async defer src="{{ $umamiScript }}"
        data-website-id="{{ $umamiId }}"
        data-auto-track="true" data-do-not-track="false" data-cache="false"></script>
    @endif
    <link rel="alternate" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">
    <link rel="alternate" type="application/rss+xml" title="RSS {{ $siteName }}" href="{{ route('feed') }}">
</head>
<body>
@if($maintenance)
  <div class="maint-banner">{{ setting('maintenance_message') }}</div>
@endif
<a class="skip-link" href="#main-content">پرش به محتوا</a>
<header class="site-header" id="site-header">
    <div class="container header-inner">
        <a class="logo" href="{{ route('home') }}" aria-label="شیرازلینوکس">
            <img src="{{ asset('media/website/logo.png') }}" alt="شیرازلینوکس" width="120" height="48"
                 decoding="async" fetchpriority="high">
        </a>

        <button class="nav-toggle" id="nav-toggle" type="button" aria-label="منو" aria-expanded="false" aria-controls="main-nav">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav" id="main-nav" aria-label="منوی اصلی">
            @foreach(\App\Support\NavMenu::items() as $item)
                @if(($item['type'] ?? 'link') === 'group')
                    <div class="nav-item has-sub">
                        <button type="button" class="nav-link sub-trigger" aria-expanded="false">{{ $item['label'] }} <span class="caret">▾</span></button>
                        <div class="submenu" role="menu">
                            @foreach($item['children'] ?? [] as $child)
                                <a href="{{ \App\Support\NavMenu::resolveUrl($child['url'] ?? '/') }}">{{ $child['label'] ?? '' }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $href = \App\Support\NavMenu::resolveUrl($item['url'] ?? '/'); @endphp
                    <a href="{{ $href }}"
                       class="nav-link{{ !empty($item['cta']) ? ' nav-cta' : '' }}{{ url()->current() === $href ? ' is-active' : '' }}">
                        {{ $item['label'] ?? '' }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="header-actions">
            <button type="button" class="icon-btn theme-toggle" id="theme-toggle"
                    aria-label="تغییر حالت روشن/تیره" title="روشن / تیره">
                <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                </svg>
                <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M21 14.5A8.5 8.5 0 1 1 9.5 3a7 7 0 0 0 11.5 11.5z"></path>
                </svg>
            </button>
            <a class="icon-btn search-btn" href="{{ route('search') }}" aria-label="جستجو" title="جستجو">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
            </a>
        </div>
    </div>
</header>

<main class="site-main" id="main-content">@yield('content')</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <strong class="footer-brand">{{ setting('site_name', 'شیرازلینوکس') }}</strong>
            <p class="muted">{{ setting('footer_text', 'جامعه نرم‌افزار آزاد شیراز') }}</p>
            <div class="footer-quick">
                <a href="{{ route('tags.show','event') }}">نشست‌ها</a>
                <a href="{{ url('/what-is-free-software') }}">نرم‌افزار آزاد</a>
                <a href="{{ route('tags.show','free-software-community-guide') }}">راهنما</a>
                <a href="{{ url('/donate') }}">حمایت</a>
            </div>
        </div>
        <div>
            <strong>پیوندها</strong>
            <p>
                <a href="{{ route('tags.index') }}">همه برچسب‌ها</a><br>
                <a href="{{ route('authors.index') }}">نویسندگان</a><br>
                <a href="{{ route('search') }}">جستجو</a><br>
                <a href="{{ route('feed') }}">RSS</a>
            </p>
        </div>
        <div>
            <strong>جامعه</strong>
            <p>
                <a href="{{ url('/about') }}">درباره ما</a><br>
                <a href="{{ url('/contact') }}">تماس</a><br>
                <a href="{{ url('/transparency') }}">شفافیت</a>
            </p>
        </div>
        <div>
            <strong>سایت</strong>
            <p class="muted"><a href="{{ setting('social_website', 'https://sudoshz.ir') }}" target="_blank" rel="noopener">{{ parse_url(setting('social_website', 'https://sudoshz.ir'), PHP_URL_HOST) ?: 'sudoshz.ir' }}</a></p>
            <p class="muted" style="margin-top:.4rem"><a href="{{ route('sitemap') }}">نقشه سایت</a> · <a href="{{ route('feed') }}">RSS</a></p>
        </div>
    </div>
    @php $socialLinks = collect(\App\Support\Settings::footerSocialLinks()); @endphp
    @if($socialLinks->isNotEmpty())
        <nav class="container footer-social-bar" aria-label="شبکه‌های اجتماعی">
            @foreach($socialLinks as $link)
                <a class="footer-social-btn" href="{{ $link['url'] }}" target="_blank" rel="me noopener"
                   title="{{ $link['label'] }}" aria-label="{{ $link['label'] }}">
                    @include('site.partials.social-icon', ['key' => $link['key']])
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif
    <div class="container footer-seals" aria-label="نشان‌ها و حامیان">
        <a class="footer-seal" href="https://shirazweb.net/?ref=sudoshz.ir" target="_blank" rel="noopener noreferrer" title="شیرازوب — حامی">
            <img src="https://sudoshz.ir/media/posts/130/shirazweb-logo-transparent-background.png"
                 alt="شیرازوب" width="150" height="60" loading="lazy" decoding="async">
        </a>
        <a class="footer-seal" referrerpolicy="origin" target="_blank" rel="noopener"
           href="https://trustseal.enamad.ir/?id=651002&Code=lsuiBjkKBPPqeHFnxv8Q1a5AdfRTcqah"
           title="نماد اعتماد الکترونیکی">
            <img referrerpolicy="origin"
                 src="https://trustseal.enamad.ir/logo.aspx?id=651002&Code=lsuiBjkKBPPqeHFnxv8Q1a5AdfRTcqah"
                 alt="اینماد" width="125" height="136" loading="lazy" decoding="async"
                 code="lsuiBjkKBPPqeHFnxv8Q1a5AdfRTcqah">
        </a>
    </div>
    <div class="container footer-bottom">
        <span>© {{ jdate(now(), 'Y') }} {{ setting('site_name', 'شیرازلینوکس') }} — جامعه نرم‌افزار آزاد شیراز</span>
        <span>sudoshz.ir</span>
    </div>
</footer>

<button type="button" class="to-top" id="to-top" aria-label="بازگشت به بالا" title="بالا">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
        <path d="M12 19V5M5 12l7-7 7 7"></path>
    </svg>
</button>

<script src="{{ asset('js/site.js') }}?v=20260818themefix" defer></script>
<script>
(function () {
  try {
    if (window.__slAnalyticsSent) return;
    window.__slAnalyticsSent = true;
    var path = location.pathname + location.search;
    if (path.indexOf('/admin') === 0) return;
    var body = JSON.stringify({ path: path, referrer: document.referrer || '' });
    var url = @json(url('/analytics/collect'));
    if (navigator.sendBeacon) {
      navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
    } else {
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: body, keepalive: true, credentials: 'same-origin' });
    }
  } catch (e) {}
})();
</script>
@stack('scripts')
</body>
</html>
