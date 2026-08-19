@extends('layouts.site')
@section('title', 'برچسب‌ها')
@section('meta_description', 'فهرست برچسب‌ها و موضوعات شیرازلینوکس: نشست، آموزش، نرم‌افزار آزاد و بیشتر.')
@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $items = [];
  $pos = 1;
  foreach ($tags as $tag) {
      $items[] = [
        '@type' => 'ListItem',
        'position' => $pos++,
        'name' => $tag->name,
        'url' => route('tags.show', $tag->slug),
      ];
  }
  $ld = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'برچسب‌ها',
    'url' => route('tags.index'),
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
      ['name' => 'برچسب‌ها', 'url' => route('tags.index')],
    ]])
    <section class="hero">
        <h1>برچسب‌ها</h1>
        <p>{{ $tags->count() }} برچسب با مطلب منتشرشده</p>
    </section>
    <div class="content-section">
        <div class="tag-cloud" style="gap:.55rem">
            @foreach($tags as $tag)
                <a href="{{ route('tags.show', $tag->slug) }}" style="font-size:.95rem">{{ $tag->name }} <span>({{ $tag->posts_count }})</span></a>
            @endforeach
        </div>
    </div>
</div>
@endsection
