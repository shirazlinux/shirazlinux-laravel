@extends('layouts.site')
@php
  use App\Support\Seo;
  $pageOg = $post->featured_image ? Seo::absolute($post->featured_image) : '';
  $pageDesc = Seo::description($post->meta_description ?: $post->excerpt ?: $post->body, 160);
  $crumbItems = [
    ['name' => 'خانه', 'url' => url('/')],
    ['name' => $post->title, 'url' => url('/'.$post->slug)],
  ];
@endphp
@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $pageDesc)
@section('og_type', 'website')
@section('og_image', $pageOg)
@section('robots', (!empty($isPreview) || ($post->status ?? 'published') !== 'published') ? 'noindex,nofollow' : '')

@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $pageLd = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $post->title,
    'headline' => $post->title,
    'url' => url('/'.$post->slug),
    'description' => $pageDesc,
    'dateModified' => optional($post->updated_at)?->toIso8601String(),
    'datePublished' => optional($post->published_at)?->toIso8601String(),
    'inLanguage' => 'fa-IR',
    'isPartOf' => ['@type' => 'WebSite', 'name' => setting('site_name'), 'url' => url('/')],
    'primaryImageOfPage' => $pageOg ? ['@type' => 'ImageObject', 'url' => $pageOg] : null,
  ], fn ($v) => $v !== null && $v !== '');
  $crumbs = Seo::breadcrumbJsonLd($crumbItems);
@endphp
<script type="application/ld+json">{!! json_encode($pageLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => $crumbItems])
    <article class="article">
        <h1>{{ $post->title }}</h1>
        @if($post->featured_image)
            <figure class="article-cover">
                <img src="{{ asset(ltrim($post->featured_image, '/')) }}" alt="{{ $post->title }}"
                     decoding="async" fetchpriority="high"
                     onerror="this.style.display='none'">
            </figure>
        @endif
        <div class="article-body prose">{!! \App\Support\ContentHtml::prepare($post->body, $post->title) !!}</div>
    </article>

    @if(setting('comments_enabled', true))
        @include('site.partials.comments', ['post' => $post])
    @endif
</div>
@endsection
