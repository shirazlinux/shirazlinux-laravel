@extends('layouts.site')
@section('title', $author->name.' | نویسنده شیرازلینوکس')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $author->bio) ?: ($author->name.' — نگارش و ترجمه مطالب جامعه نرم‌افزار آزاد شیراز.'), 160))
@section('canonical', route('authors.show', $author->slug))
@if($author->avatar)
@section('og_image', str_starts_with($author->avatar,'http') ? $author->avatar : asset(ltrim($author->avatar,'/')))
@endif

@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $person = array_filter([
    '@context'=>'https://schema.org','@type'=>'Person','name'=>$author->name,
    'url'=>route('authors.show',$author->slug),
    'description'=>\Illuminate\Support\Str::limit(strip_tags((string)$author->bio), 200),
    'image'=>$author->avatar ? (str_starts_with($author->avatar,'http')?$author->avatar:asset(ltrim($author->avatar,'/'))) : null,
    'sameAs'=>array_values(array_filter([$author->website,$author->telegram,$author->mastodon,$author->github])),
    'worksFor' => [
      '@type' => 'Organization',
      'name' => setting('seo_organization_name', setting('site_name', 'شیرازلینوکس')),
      'url' => url('/'),
    ],
  ]);
  $crumbs = [
    '@context'=>'https://schema.org','@type'=>'BreadcrumbList',
    'itemListElement'=>[
      ['@type'=>'ListItem','position'=>1,'name'=>'خانه','item'=>url('/')],
      ['@type'=>'ListItem','position'=>2,'name'=>'نویسندگان','item'=>route('authors.index')],
      ['@type'=>'ListItem','position'=>3,'name'=>$author->name,'item'=>route('authors.show',$author->slug)],
    ],
  ];
@endphp
<script type="application/ld+json">{!! json_encode($person, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => [
      ['name' => 'خانه', 'url' => url('/')],
      ['name' => 'نویسندگان', 'url' => route('authors.index')],
      ['name' => $author->name, 'url' => route('authors.show', $author->slug)],
    ]])
    <section class="hero author-hero">
        <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap">
          @if($author->avatar)
            <img src="{{ str_starts_with($author->avatar,'http') ? $author->avatar : asset(ltrim($author->avatar,'/')) }}"
                 alt="{{ $author->name }}" width="88" height="88"
                 style="border-radius:999px;object-fit:cover;border:1px solid var(--border)">
          @endif
          <div>
            <p class="section-hero-kicker">نگارش و ترجمه</p>
            <h1 style="margin:0 0 .4rem">{{ $author->name }}</h1>
            @if($author->bio)<p class="muted">{{ $author->bio }}</p>
            @else
              <p class="muted">در نگارش یا ترجمه مطالب شیرازلینوکس نقش داشته است.</p>
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.5rem">
              @foreach([
                'website'=>'وب','telegram'=>'تلگرام','mastodon'=>'ماستادون','github'=>'گیت'
              ] as $field => $label)
                @if(!empty($author->{$field}))
                  <a class="badge" href="{{ $author->{$field} }}" target="_blank" rel="noopener me">{{ $label }}</a>
                @endif
              @endforeach
            </div>
          </div>
        </div>
    </section>
    @if($posts->isNotEmpty())
    <section class="content-section">
        <div class="section-head"><h2 class="section-title">نوشته‌ها</h2></div>
        <div class="grid">
            @foreach($posts as $post)
                @include('site.partials.card', ['post' => $post])
            @endforeach
        </div>
        <div class="pagination">{!! $posts->withQueryString()->links() !!}</div>
    </section>
    @endif
    @if(isset($contributed) && $contributed->isNotEmpty())
    <section class="content-section">
        <div class="section-head"><h2 class="section-title">مشارکت در نگارش یا ترجمه</h2></div>
        <div class="grid">
            @foreach($contributed as $post)
                @include('site.partials.card', ['post' => $post])
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
