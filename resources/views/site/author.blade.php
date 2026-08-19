@extends('layouts.site')
@section('title', $author->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $author->bio) ?: $author->name, 160))
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
            <h1 style="margin:0 0 .4rem">{{ $author->name }}</h1>
            @if($author->bio)<p class="muted">{{ $author->bio }}</p>@endif
            <div style="display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.5rem">
              @foreach([
                'website'=>'وب','telegram'=>'تلگرام','mastodon'=>'ماس‌تودون','github'=>'گیت'
              ] as $field => $label)
                @if(!empty($author->{$field}))
                  <a class="badge" href="{{ $author->{$field} }}" target="_blank" rel="noopener me">{{ $label }}</a>
                @endif
              @endforeach
            </div>
          </div>
        </div>
    </section>
    <div class="grid">
        @foreach($posts as $post)
            @include('site.partials.card', ['post' => $post])
        @endforeach
    </div>
    <div class="pagination">{!! $posts->withQueryString()->links() !!}</div>
</div>
@endsection
