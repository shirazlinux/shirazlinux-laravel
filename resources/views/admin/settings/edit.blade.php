@extends('layouts.admin')
@section('title', 'تنظیمات سایت')
@section('content')
@php
  $tabLabels = [
    'general' => 'عمومی',
    'seo' => 'سئو',
    'display' => 'نمایش',
    'social' => 'شبکه‌ها',
    'integrations' => 'یکپارچه‌سازی',
  ];
  $v = fn (string $key) => old($key, $settings[$key] ?? $defaults[$key] ?? '');
  $checked = fn (string $key) => (bool) old($key, $settings[$key] ?? false);
@endphp

<div class="settings-page">
  <div class="settings-hero">
    <div>
      <p class="composer-kicker">مدیریت وب‌سایت</p>
      <h1 class="composer-title" style="margin:0">تنظیمات سایت</h1>
      <p class="settings-lead">سئو، ظاهر صفحهٔ اصلی، شبکه‌های اجتماعی و اتصال‌ها را از اینجا مدیریت کنید.</p>
    </div>
  </div>

  <nav class="settings-tabs" aria-label="بخش‌های تنظیمات">
    @foreach($tabLabels as $key => $label)
      <a class="settings-tab{{ $tab === $key ? ' is-active' : '' }}"
         href="{{ route('admin.settings.edit', ['tab' => $key]) }}">{{ $label }}</a>
    @endforeach
  </nav>

  <form class="settings-card" method="post" action="{{ route('admin.settings.update') }}">
    @csrf
    <input type="hidden" name="tab" value="{{ $tab }}">

    @if($tab === 'general')
      <h2 class="settings-section-title">اطلاعات کلی</h2>
      <div class="settings-grid">
        <div class="settings-field">
          <label>نام سایت</label>
          <input name="site_name" value="{{ $v('site_name') }}" required>
        </div>
        <div class="settings-field">
          <label>شعار / تگ‌لاین</label>
          <input name="site_tagline" value="{{ $v('site_tagline') }}">
        </div>
        <div class="settings-field settings-field--full">
          <label>توضیح کوتاه سایت</label>
          <textarea name="site_description" rows="3">{{ $v('site_description') }}</textarea>
        </div>
        <div class="settings-field">
          <label>ایمیل تماس</label>
          <input type="email" name="contact_email" value="{{ $v('contact_email') }}" dir="ltr" placeholder="hello@example.com">
        </div>
        <div class="settings-field">
          <label>متن کوتاه فوتر</label>
          <input name="footer_text" value="{{ $v('footer_text') }}">
        </div>
      </div>
    @endif

    @if($tab === 'seo')
      <h2 class="settings-section-title">سئو و متا</h2>
      <p class="settings-help">عنوان، توضیحات، Open Graph، Twitter Card، canonical، robots و دادهٔ ساختاریافته (JSON-LD) از اینجا کنترل می‌شود.</p>
      <div class="settings-grid">
        <div class="settings-field">
          <label>پسوند عنوان</label>
          <input name="seo_title_suffix" value="{{ $v('seo_title_suffix') }}" placeholder="جامعه نرم‌افزار آزاد">
          <small>عنوان صفحه | <b>پسوند</b></small>
        </div>
        <div class="settings-field">
          <label>robots</label>
          <input name="seo_robots" value="{{ $v('seo_robots') }}" dir="ltr" placeholder="index,follow">
        </div>
        <div class="settings-field settings-field--full">
          <label>توضیح متای پیش‌فرض (meta description)</label>
          <textarea name="seo_default_description" rows="3">{{ $v('seo_default_description') }}</textarea>
        </div>
        <div class="settings-field settings-field--full">
          <label>کلمات کلیدی (keywords)</label>
          <input name="seo_keywords" value="{{ $v('seo_keywords') }}" placeholder="شیرازلینوکس, نرم‌افزار آزاد, …">
        </div>
        <div class="settings-field">
          <label>تصویر Open Graph پیش‌فرض</label>
          <input name="seo_og_image" value="{{ $v('seo_og_image') }}" dir="ltr" placeholder="media/… یا https://">
        </div>
        <div class="settings-field">
          <label>شناسه X / Twitter</label>
          <input name="seo_twitter" value="{{ $v('seo_twitter') }}" dir="ltr" placeholder="@username">
        </div>
        <div class="settings-field">
          <label>locale (og:locale)</label>
          <input name="seo_locale" value="{{ $v('seo_locale') }}" dir="ltr" placeholder="fa_IR">
        </div>
        <div class="settings-field">
          <label>پایه canonical</label>
          <input name="seo_canonical_base" value="{{ $v('seo_canonical_base') }}" dir="ltr" placeholder="https://sudoshz.ir">
        </div>
      </div>

      <h3 class="settings-subtitle">تأیید موتورهای جستجو</h3>
      <p class="settings-help">
        برای نمایش سایت در گوگل با <b>سایت‌لینک</b> (لینک‌های فرعی زیر نتیجهٔ اصلی):
        ۱) سایت را در <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Search Console</a> تأیید کنید،
        ۲) نقشهٔ سایت <code dir="ltr">https://sudoshz.ir/sitemap.xml</code> را ثبت کنید،
        ۳) برای برند «شیرازلینوکس» و «sudoshz» درخواست ایندکس صفحهٔ اصلی بدهید.
        گوگل خودش سایت‌لینک‌ها را انتخاب می‌کند؛ ساختار منو و پیوندهای مهم سایت به آن کمک می‌کند.
      </p>
      <div class="settings-grid">
        <div class="settings-field">
          <label>Google Search Console</label>
          <input name="seo_google_verification" value="{{ $v('seo_google_verification') }}" dir="ltr" placeholder="content=…">
        </div>
        <div class="settings-field">
          <label>Bing Webmaster</label>
          <input name="seo_bing_verification" value="{{ $v('seo_bing_verification') }}" dir="ltr">
        </div>
        <div class="settings-field">
          <label>Yandex</label>
          <input name="seo_yandex_verification" value="{{ $v('seo_yandex_verification') }}" dir="ltr">
        </div>
      </div>

      <h3 class="settings-subtitle">دادهٔ ساختاریافته (Schema.org)</h3>
      <div class="settings-grid">
        <div class="settings-field">
          <label>نام سازمان</label>
          <input name="seo_organization_name" value="{{ $v('seo_organization_name') }}">
        </div>
        <div class="settings-field">
          <label>لوگوی سازمان</label>
          <input name="seo_organization_logo" value="{{ $v('seo_organization_logo') }}" dir="ltr" placeholder="media/website/logo.png">
        </div>
        <div class="settings-field settings-field--full">
          <label class="rail-toggle settings-toggle" style="margin:0">
            <input type="checkbox" name="seo_jsonld_enabled" value="1" @checked($checked('seo_jsonld_enabled'))>
            <span class="rail-toggle-ui" aria-hidden="true"></span>
            <span class="rail-toggle-text"><strong>JSON-LD فعال</strong><small>Organization + WebSite (+ SearchAction)</small></span>
          </label>
        </div>
        <div class="settings-field settings-field--full">
          <label class="rail-toggle settings-toggle" style="margin:0">
            <input type="checkbox" name="seo_search_action" value="1" @checked($checked('seo_search_action'))>
            <span class="rail-toggle-ui" aria-hidden="true"></span>
            <span class="rail-toggle-text"><strong>SearchAction</strong><small>جستجوی سایت در نتایج گوگل (در صورت پشتیبانی)</small></span>
          </label>
        </div>
      </div>
    @endif

    @if($tab === 'display')
      <h2 class="settings-section-title">حالت نمایشی</h2>
      <div class="settings-grid">
        <div class="settings-field">
          <label>تعداد مطلب در هر صفحه</label>
          <input type="number" min="3" max="48" name="posts_per_page" value="{{ $v('posts_per_page') }}">
        </div>
        <div class="settings-field">
          <label>تم پیش‌فرض بازدیدکننده</label>
          <select name="default_theme">
            @foreach(['system'=>'پیروی از سیستم','light'=>'روشن','dark'=>'تیره'] as $k=>$label)
              <option value="{{ $k }}" @selected($v('default_theme')===$k)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <h3 class="settings-subtitle">بخش‌های صفحهٔ اصلی</h3>
      <div class="settings-toggles">
        <p class="settings-help">برای ویرایش متن معرفی و اسلایدر به <a href="{{ route('admin.home.edit') }}">صفحه اصلی و اسلایدر</a> بروید.</p>
        @foreach([
          'home_show_slider' => ['اسلایدر','اسلایدهای بالای خانه'],
          'home_show_edu' => ['آموزش نرم‌افزار آزاد','چهار آزادی و معرفی کوتاه'],
          'home_show_history' => ['تاریخچه','کارت‌های تاریخ جنبش'],
          'home_show_projects' => ['پروژه‌های لانچ‌شده','شبکهٔ پروژه‌ها'],
          'home_show_videos' => ['ویدیوها','بخش ویدیو در خانه'],
          'comments_enabled' => ['نظرات','فرم نظر زیر مطالب'],
        ] as $key => [$title, $desc])
          <label class="rail-toggle settings-toggle">
            <input type="checkbox" name="{{ $key }}" value="1" @checked($checked($key))>
            <span class="rail-toggle-ui" aria-hidden="true"></span>
            <span class="rail-toggle-text">
              <strong>{{ $title }}</strong>
              <small>{{ $desc }}</small>
            </span>
          </label>
        @endforeach
      </div>
    @endif

    @if($tab === 'social')
      <h2 class="settings-section-title">شبکه‌ها و پیوندها</h2>
      <div class="settings-grid">
        @foreach([
          'social_website' => 'وب‌سایت اصلی',
          'social_telegram' => 'تلگرام',
          'social_mastodon' => 'ماس‌تودون',
          'social_matrix' => 'ماتریکس',
          'social_codeberg' => 'Codeberg / گیت',
          'social_youtube' => 'یوتیوب / ویدیو',
        ] as $key => $label)
          <div class="settings-field">
            <label>{{ $label }}</label>
            <input name="{{ $key }}" value="{{ $v($key) }}" dir="ltr" placeholder="https://…">
          </div>
        @endforeach
      </div>
    @endif

    @if($tab === 'integrations')
      <h2 class="settings-section-title">آمار و اتصال‌ها</h2>
      <p class="settings-help">توکن محرمانهٔ Umami فقط در <code dir="ltr">.env</code> با کلید <code dir="ltr">UMAMI_API_TOKEN</code> تنظیم می‌شود (امن‌تر از ذخیره در دیتابیس).</p>
      <div class="settings-grid">
        <div class="settings-field settings-field--full">
          <label class="rail-toggle settings-toggle" style="margin:0">
            <input type="checkbox" name="umami_enabled" value="1" @checked($checked('umami_enabled'))>
            <span class="rail-toggle-ui" aria-hidden="true"></span>
            <span class="rail-toggle-text">
              <strong>فعال‌سازی ردیاب Umami در سایت</strong>
              <small>اسکریپت در صفحات عمومی لود شود</small>
            </span>
          </label>
        </div>
        <div class="settings-field">
          <label>Website ID</label>
          <input name="umami_website_id" value="{{ $v('umami_website_id') }}" dir="ltr">
        </div>
        <div class="settings-field">
          <label>آدرس script.js</label>
          <input name="umami_script_url" value="{{ $v('umami_script_url') }}" dir="ltr">
        </div>
      </div>

      <h3 class="settings-subtitle">حالت نگهداری</h3>
      <div class="settings-grid">
        <div class="settings-field settings-field--full">
          <label class="rail-toggle settings-toggle" style="margin:0">
            <input type="checkbox" name="maintenance_mode" value="1" @checked($checked('maintenance_mode'))>
            <span class="rail-toggle-ui" aria-hidden="true"></span>
            <span class="rail-toggle-text">
              <strong>حالت نگهداری برای بازدیدکنندگان</strong>
              <small>ادمین همچنان وارد می‌شود</small>
            </span>
          </label>
        </div>
        <div class="settings-field settings-field--full">
          <label>پیام نگهداری</label>
          <textarea name="maintenance_message" rows="2">{{ $v('maintenance_message') }}</textarea>
        </div>
      </div>

      <h3 class="settings-subtitle">robots.txt</h3>
      <div class="settings-field settings-field--full">
        <label>محتوای robots.txt</label>
        <textarea name="robots_txt" rows="6" dir="ltr" style="font-family:ui-monospace,monospace;font-size:.85rem">{{ $v('robots_txt') }}</textarea>
        <small>می‌توانید <code>{sitemap}</code> بگذارید تا با آدرس sitemap جایگزین شود. مسیر: <code dir="ltr">/robots.txt</code></small>
      </div>
    @endif

    <div class="settings-actions">
      <button class="btn" type="submit">ذخیرهٔ این بخش</button>
      <a class="btn light" href="{{ route('home') }}" target="_blank">مشاهده سایت</a>
    </div>
  </form>
</div>
@endsection
