@php
  use App\Support\Seo;

  $siteName = setting('site_name', 'شیرازلینوکس');
  $titleSuffix = setting('seo_title_suffix', 'جامعه نرم‌افزار آزاد');
  $pageTitle = trim($__env->yieldContent('title', $siteName));
  // Strip zero-width / BOM junk that sometimes sneaks into imported titles
  $pageTitle = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $pageTitle) ?? $pageTitle;
  $pageTitle = trim($pageTitle);
  // Home / site-name titles: avoid "خانه | …" redundancy
  if (in_array($pageTitle, ['خانه', 'Home', $siteName], true)) {
      $fullTitle = $siteName.($titleSuffix ? ' | '.$titleSuffix : '');
      $pageTitle = $siteName;
  } else {
      $fullTitle = $pageTitle.($titleSuffix ? ' | '.$titleSuffix : '');
  }

  $desc = Seo::description(
      trim($__env->yieldContent('meta_description', setting('seo_default_description', setting('site_description', '')))),
      160
  );

  $robots = trim($__env->yieldContent('robots', ''));
  if ($robots === '') {
      $robots = setting('seo_robots', 'index,follow');
  }
  if (setting('maintenance_mode') && ! auth()->check()) {
      $robots = 'noindex,nofollow';
  }

  $ogImage = trim($__env->yieldContent('og_image', ''));
  if ($ogImage === '') {
      $ogImage = Seo::defaultOgImage();
  } else {
      $ogImage = Seo::absolute($ogImage);
  }

  $canonical = Seo::canonical(trim($__env->yieldContent('canonical', '')) ?: null);
  $locale = setting('seo_locale', 'fa_IR');
  $ogType = trim($__env->yieldContent('og_type', setting('seo_og_type_default', 'website')));
  $twitter = ltrim((string) setting('seo_twitter', ''), '@');
  $keywords = setting('seo_keywords', '');
  $orgName = setting('seo_organization_name', $siteName);
  $orgLogo = setting('seo_organization_logo', 'media/website/logo.png');
  $orgLogoUrl = Seo::absolute($orgLogo);
  $jsonLdOn = (bool) setting('seo_jsonld_enabled', true);
  $searchAction = (bool) setting('seo_search_action', true);
  $sameAs = Seo::sameAs();
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $desc }}">
<meta name="robots" content="{{ $robots }}">
<meta name="googlebot" content="{{ $robots }}">
<meta name="author" content="{{ $orgName }}">
<meta name="language" content="fa">
<meta name="revisit-after" content="7 days">
@if($keywords)
<meta name="keywords" content="{{ $keywords }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<link rel="alternate" hreflang="fa" href="{{ $canonical }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}">
@if(setting('seo_google_verification'))
<meta name="google-site-verification" content="{{ setting('seo_google_verification') }}">
@endif
@if(setting('seo_bing_verification'))
<meta name="msvalidate.01" content="{{ setting('seo_bing_verification') }}">
@endif
@if(setting('seo_yandex_verification'))
<meta name="yandex-verification" content="{{ setting('seo_yandex_verification') }}">
@endif

{{-- Open Graph --}}
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $pageTitle }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if($twitter)
<meta name="twitter:site" content="@{{ $twitter }}">
<meta name="twitter:creator" content="@{{ $twitter }}">
@endif

@stack('seo')

@if($jsonLdOn)
@php
  $navElements = Seo::siteNavigationElements();
  $org = [
    '@type' => 'Organization',
    '@id' => url('/').'#organization',
    'name' => $orgName,
    'alternateName' => array_values(array_unique(array_filter([
      $siteName,
      'شیراز لینوکس',
      'ShirazLinux',
      'Shiraz Linux',
      'sudoshz',
      'sudoshz.ir',
    ]))),
    'url' => url('/'),
    'logo' => [
      '@type' => 'ImageObject',
      'url' => $orgLogoUrl,
      'width' => 512,
      'height' => 512,
    ],
    'image' => $orgLogoUrl,
    'description' => Seo::description(setting('site_description', $desc), 200),
    'foundingLocation' => [
      '@type' => 'Place',
      'name' => 'شیراز',
      'address' => [
        '@type' => 'PostalAddress',
        'addressLocality' => 'شیراز',
        'addressRegion' => 'فارس',
        'addressCountry' => 'IR',
      ],
    ],
    'areaServed' => [
      '@type' => 'City',
      'name' => 'شیراز',
    ],
    'knowsAbout' => [
      'نرم‌افزار آزاد',
      'گنو/لینوکس',
      'جامعه نرم‌افزار آزاد',
      'FOSS',
    ],
  ];
  if ($sameAs) {
      $org['sameAs'] = $sameAs;
  }
  $website = [
    '@type' => 'WebSite',
    '@id' => url('/').'#website',
    'name' => $siteName,
    'alternateName' => ['شیراز لینوکس', 'ShirazLinux', 'sudoshz.ir'],
    'url' => url('/'),
    'description' => $desc,
    'inLanguage' => 'fa-IR',
    'publisher' => ['@id' => url('/').'#organization'],
  ];
  if ($searchAction) {
      $website['potentialAction'] = [
        '@type' => 'SearchAction',
        'target' => [
          '@type' => 'EntryPoint',
          'urlTemplate' => url('/search').'?q={search_term_string}',
        ],
        'query-input' => 'required name=search_term_string',
      ];
  }
  if ($navElements) {
      $website['hasPart'] = $navElements;
  }
  $webpage = [
    '@type' => 'WebPage',
    '@id' => $canonical.'#webpage',
    'url' => $canonical,
    'name' => $pageTitle,
    'description' => $desc,
    'isPartOf' => ['@id' => url('/').'#website'],
    'about' => ['@id' => url('/').'#organization'],
    'inLanguage' => 'fa-IR',
    'primaryImageOfPage' => [
      '@type' => 'ImageObject',
      'url' => $ogImage,
    ],
  ];
  $graph = [$org, $website, $webpage];
  // On homepage, expose primary sitelink destinations explicitly
  if (request()->routeIs('home') || rtrim($canonical, '/') === rtrim(url('/'), '/')) {
      $graph[] = Seo::primaryLinksItemList();
  }
  $jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => $graph,
  ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
