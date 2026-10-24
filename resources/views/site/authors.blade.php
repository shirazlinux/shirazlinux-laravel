@extends('layouts.site')
@section('title', 'نویسندگان و مترجمان')
@section('meta_description', 'کسانی که در نگارش و ترجمه مطالب شیرازلینوکس دست داشته‌اند — مقالات، راهنماها و برگردان‌های نرم‌افزار آزاد.')
@section('canonical', route('authors.index'))
@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $items = [];
  $pos = 1;
  foreach ($authors as $author) {
      $items[] = [
        '@type' => 'ListItem',
        'position' => $pos++,
        'name' => $author->name,
        'url' => route('authors.show', $author->slug),
      ];
  }
  $ld = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'نویسندگان',
    'url' => route('authors.index'),
    'isPartOf' => ['@type' => 'WebSite', 'name' => setting('site_name'), 'url' => url('/')],
    'mainEntity' => [
      '@type' => 'ItemList',
      'numberOfItems' => count($items),
      'itemListElement' => $items,
    ],
  ];
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush
@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => [
      ['name' => 'خانه', 'url' => url('/')],
      ['name' => 'نویسندگان', 'url' => route('authors.index')],
    ]])
    <section class="hero">
        <h1>نویسندگان و مترجمان</h1>
        <p class="muted">کسانی که در نگارش یا ترجمه مطالب جامعه دست داشته‌اند.</p>
    </section>
    <div class="crew-grid author-grid">
        @foreach($authors as $author)
            <article class="crew-card">
              <a class="crew-card-link" href="{{ route('authors.show', $author->slug) }}">
                @if($author->avatarUrl())
                  <img class="crew-card-photo" src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" width="96" height="96" loading="lazy">
                @else
                  <div class="crew-card-photo crew-card-photo--empty" aria-hidden="true">{{ mb_substr($author->name, 0, 1) }}</div>
                @endif
                <h2 class="crew-card-name">{{ $author->name }}</h2>
                <p class="crew-card-role">{{ $author->posts_count }} مطلب@if($author->contributions_count) · {{ $author->contributions_count }} مشارکت@endif</p>
              </a>
            </article>
        @endforeach
    </div>
</div>
@endsection
