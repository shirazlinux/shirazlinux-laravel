@extends('layouts.site')
@php
  use App\Support\Seo;
  $postOg = $post->featured_image
    ? Seo::absolute($post->featured_image)
    : '';
  $postDesc = Seo::description($post->meta_description ?: $post->excerpt ?: $post->body, 160);
  $crumbItems = array_values(array_filter([
    ['name' => 'خانه', 'url' => url('/')],
    $post->tags->first()
      ? ['name' => $post->tags->first()->name, 'url' => route('tags.show', $post->tags->first()->slug)]
      : ['name' => 'مطالب', 'url' => route('tags.show', 'post')],
    ['name' => $post->title, 'url' => url('/'.$post->slug)],
  ]));
@endphp
@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $postDesc)
@section('og_type', 'article')
@section('og_image', $postOg)
@section('robots', (!empty($isPreview) || ($post->status ?? 'published') !== 'published') ? 'noindex,nofollow' : '')

@push('seo')
@if($post->published_at)
<meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
@endif
@if($post->updated_at)
<meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
@endif
@if($post->author)
<meta property="article:author" content="{{ $post->author->name }}">
@endif
@foreach($post->tags as $tag)
<meta property="article:tag" content="{{ $tag->name }}">
@endforeach
@if(setting('seo_jsonld_enabled', true))
@php
  $articleLd = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post->title,
    'datePublished' => optional($post->published_at)?->toIso8601String(),
    'dateModified' => optional($post->updated_at)?->toIso8601String(),
    'author' => $post->author ? [
      '@type' => 'Person',
      'name' => $post->author->name,
      'url' => route('authors.show', $post->author->slug),
    ] : [
      '@type' => 'Organization',
      'name' => setting('seo_organization_name', setting('site_name', 'شیرازلینوکس')),
    ],
    'publisher' => [
      '@type' => 'Organization',
      'name' => setting('seo_organization_name', setting('site_name', 'شیرازلینوکس')),
      'logo' => [
        '@type' => 'ImageObject',
        'url' => Seo::absolute(setting('seo_organization_logo', 'media/website/logo.png')),
      ],
    ],
    'mainEntityOfPage' => [
      '@type' => 'WebPage',
      '@id' => url('/'.$post->slug),
    ],
    'image' => $postOg ?: Seo::defaultOgImage(),
    'description' => $postDesc,
    'inLanguage' => 'fa-IR',
    'isAccessibleForFree' => true,
    'articleSection' => $post->tags->first()?->name,
    'wordCount' => str_word_count(strip_tags((string) $post->body)),
  ], fn ($v) => $v !== null && $v !== '');
  $crumbs = Seo::breadcrumbJsonLd($crumbItems);
@endphp
<script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@if(!empty($isPreview))
<div class="maint-banner" style="background:#7c2d12">پیش‌نمایش — این صفحه عمومی نیست</div>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => $crumbItems])
    <article class="article" itemscope itemtype="https://schema.org/BlogPosting">
        <h1 itemprop="headline">{{ $post->title }}</h1>
        <div class="article-meta">
            @if($post->author)
                <a href="{{ route('authors.show', $post->author->slug) }}" itemprop="author">{{ $post->author->name }}</a>
            @endif
            @if($post->published_at)
                · <time datetime="{{ $post->published_at->toIso8601String() }}" itemprop="datePublished">{{ jdate($post->published_at) }}</time>
            @endif
            @if($post->updated_at)
                <meta itemprop="dateModified" content="{{ $post->updated_at->toIso8601String() }}">
            @endif
        </div>
        @if($post->featured_image)
            <figure class="article-cover">
                <img src="{{ asset(ltrim($post->featured_image, '/')) }}" alt="{{ $post->title }}"
                     itemprop="image" width="1200" height="630"
                     decoding="async" fetchpriority="high"
                     onerror="this.style.display='none'">
            </figure>
        @endif
        <div class="article-body prose" itemprop="articleBody">{!! \App\Support\ContentHtml::prepare($post->body, $post->title) !!}</div>
        <div style="margin-top:1.25rem" aria-label="برچسب‌ها">
            @foreach($post->tags as $tag)
                <a class="badge" rel="tag" href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>
            @endforeach
        </div>
    </article>

    @if($related->isNotEmpty())
        <h2 style="margin-top:2rem">مرتبط</h2>
        <div class="grid">
            @foreach($related as $item)
                @include('site.partials.card', ['post' => $item])
            @endforeach
        </div>
    @endif

    @if(setting('comments_enabled', true))
        @include('site.partials.comments', ['post' => $post])
    @endif
</div>
@endsection
