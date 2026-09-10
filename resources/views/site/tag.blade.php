@extends('layouts.site')
@php
  $tagCover = $tag->featuredImagePath();
  $tagOg = $tagCover
    ? (str_starts_with($tagCover, 'http') ? $tagCover : asset($tagCover))
    : '';
@endphp
@section('title', $tag->meta_title ?: $tag->name)
@section('meta_description', $tag->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) $tag->description)) ?: $tag->name, 160))
@section('og_image', $tagOg)

@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $tagLd = [
    '@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$tag->name,
    'url'=>route('tags.show',$tag->slug),
    'description'=>\Illuminate\Support\Str::limit(strip_tags((string)($tag->meta_description ?: $tag->description)), 200),
  ];
  $tagCrumbs = [
    '@context'=>'https://schema.org','@type'=>'BreadcrumbList',
    'itemListElement'=>[
      ['@type'=>'ListItem','position'=>1,'name'=>'خانه','item'=>url('/')],
      ['@type'=>'ListItem','position'=>2,'name'=>'برچسب‌ها','item'=>route('tags.index')],
      ['@type'=>'ListItem','position'=>3,'name'=>$tag->name,'item'=>route('tags.show',$tag->slug)],
    ],
  ];
@endphp
<script type="application/ld+json">{!! json_encode($tagLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($tagCrumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
@php
    $isEvent = $tag->isEventLike();
    $isPoster = $tag->isPosterLike();
    $isGuide = $tag->isGuideLike();
    $cover = $tag->featuredImagePath();
    $kicker = $isEvent
        ? 'رویدادها'
        : ($tag->slug === 'post'
            ? 'پست‌ها و مقالات'
            : ($tag->slug === 'videos'
                ? 'ویدیو'
                : ($isGuide ? 'راهنما و آموزش' : 'بخش')));
    $tagCrumbsUi = [
        ['name' => 'خانه', 'url' => url('/')],
        ['name' => 'برچسب‌ها', 'url' => route('tags.index')],
        ['name' => $tag->name, 'url' => route('tags.show', $tag->slug)],
    ];
@endphp
<div class="container tag-page {{ $isEvent ? 'tag-page--event' : '' }} {{ $isPoster ? 'tag-page--poster' : '' }} {{ $isGuide ? 'tag-page--guide' : '' }}">
    @include('site.partials.breadcrumbs', ['items' => $tagCrumbsUi])

    <header class="section-hero {{ $cover ? 'has-cover' : '' }} {{ $isEvent ? 'section-hero--event' : '' }} {{ $isGuide ? 'section-hero--guide' : '' }}">
        <div class="section-hero-main">
            <p class="section-hero-kicker">{{ $kicker }}</p>
            <h1 class="section-hero-title">{{ $tag->name }}</h1>
            <p class="section-hero-count">
                <span class="count-pill">{{ $posts->total() }}</span>
                مطلب در این بخش
            </p>
            @if($tag->description)
                <div class="section-hero-desc prose-soft">
                    {!! $tag->description !!}
                </div>
            @endif
        </div>
        @if($cover)
            <figure class="section-hero-cover">
                <img src="{{ asset($cover) }}" alt="{{ $tag->name }}" loading="eager">
            </figure>
        @endif
    </header>

    <div class="section-head tag-list-head">
        <h2 class="section-title">{{ $isEvent ? 'رویدادها و مطالب این بخش' : 'مطالب این بخش' }}</h2>
        <span class="muted">{{ $posts->total() }} مورد</span>
    </div>

    <div class="grid {{ ($isEvent || $isPoster) ? 'cards-event' : ($isGuide ? 'cards-guide' : 'cards-3') }}">
        @forelse($posts as $post)
            @include('site.partials.card', [
                'post' => $post,
                'imageAspect' => ($isEvent || $isPoster) ? '1/1' : ($isGuide ? '16/9' : null),
            ])
        @empty
            <p class="empty-state">هنوز مطلبی در این برچسب نیست.</p>
        @endforelse
    </div>
    <div class="pagination">{!! $posts->withQueryString()->links() !!}</div>
</div>
@endsection
