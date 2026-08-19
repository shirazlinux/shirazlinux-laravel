@extends('layouts.site')
@section('title', 'نویسندگان')
@section('meta_description', 'نویسندگان و اعضای جامعه شیرازلینوکس — فهرست نویسندگان مطالب و راهنماها.')
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
        <h1>نویسندگان</h1>
        <p class="muted">افرادی که در تولید محتوای جامعه نقش داشته‌اند.</p>
    </section>
    <ul class="list-plain">
        @foreach($authors as $author)
            <li>
                <a href="{{ route('authors.show', $author->slug) }}"><strong>{{ $author->name }}</strong></a>
                <span class="card-meta"> — {{ $author->posts_count }} مطلب</span>
            </li>
        @endforeach
    </ul>
</div>
@endsection
