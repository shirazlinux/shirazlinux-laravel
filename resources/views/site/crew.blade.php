@extends('layouts.site')
@php
  $pageTitle = setting('crew_page_title', 'تیم اجرایی شیرازلینوکس');
  $pageIntro = setting('crew_page_intro', 'کادر اجرایی شیرازلینوکس داوطلبانه نشست‌ها، دورهمی‌ها، کارگاه‌ها و همایش‌ها را جلو می‌برد.');
@endphp
@section('title', $pageTitle)
@section('meta_description', \Illuminate\Support\Str::limit($pageIntro, 160))
@section('canonical', route('crew.index'))

@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $people = $current->concat($former)->map(fn ($m) => array_filter([
    '@type' => 'Person',
    'name' => $m->name,
    'jobTitle' => $m->role,
    'image' => $m->photoUrl(),
    'description' => $m->bio,
  ]))->values()->all();
  $ld = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $pageTitle,
    'url' => route('crew.index'),
    'description' => \Illuminate\Support\Str::limit($pageIntro, 200),
    'isPartOf' => ['@type' => 'WebSite', 'name' => setting('site_name'), 'url' => url('/')],
    'mainEntity' => [
      '@type' => 'ItemList',
      'numberOfItems' => count($people),
      'itemListElement' => collect($people)->values()->map(fn ($p, $i) => [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'item' => $p,
      ])->all(),
    ],
  ];
  $crumbs = \App\Support\Seo::breadcrumbJsonLd([
    ['name' => 'خانه', 'url' => url('/')],
    ['name' => $pageTitle, 'url' => route('crew.index')],
  ]);
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => [
      ['name' => 'خانه', 'url' => url('/')],
      ['name' => 'درباره ما', 'url' => url('/about')],
      ['name' => $pageTitle, 'url' => route('crew.index')],
    ]])
    <header class="section-hero">
        <div class="section-hero-main">
            <p class="section-hero-kicker">درباره ما</p>
            <h1 class="section-hero-title">{{ $pageTitle }}</h1>
            <div class="section-hero-desc prose-soft">
                <p>{!! nl2br(e($pageIntro)) !!}</p>
            </div>
        </div>
    </header>

    @include('site.partials.crew-cards', [
      'crew' => $current,
      'crewTitle' => 'اعضای فعلی',
      'crewLead' => 'این افراد امروز هماهنگی و اجرای رویدادهای جامعه را جلو می‌برند.',
      'crewHeadingId' => 'crew-current',
      'showBio' => true,
    ])

    @include('site.partials.crew-cards', [
      'crew' => $former,
      'crewTitle' => 'اعضای سابق',
      'crewLead' => 'کسانی که قبلا در کادر اجرایی نقش داشتند و بخشی از مسیر جامعه بودند.',
      'crewHeadingId' => 'crew-former',
      'showBio' => true,
    ])
</div>
@endsection
