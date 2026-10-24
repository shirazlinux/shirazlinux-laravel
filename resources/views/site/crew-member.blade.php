@extends('layouts.site')
@section('title', $member->name.' | کادر اجرایی')
@section('meta_description', \Illuminate\Support\Str::limit(($member->role ? $member->name.' — '.$member->role.'. ' : $member->name.' در کادر اجرایی شیرازلینوکس. ').strip_tags((string) $member->bio), 160))
@if($member->photoUrl())
@section('og_image', $member->photoUrl())
@endif
@section('canonical', $member->profileUrl())

@push('seo')
@if(setting('seo_jsonld_enabled', true))
@php
  $person = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $member->name,
    'jobTitle' => $member->role,
    'url' => $member->profileUrl(),
    'image' => $member->photoUrl(),
    'description' => \Illuminate\Support\Str::limit(strip_tags((string) $member->bio), 200),
  ]);
  $crumbs = \App\Support\Seo::breadcrumbJsonLd([
    ['name' => 'خانه', 'url' => url('/')],
    ['name' => $member->name, 'url' => $member->profileUrl()],
  ]);
@endphp
<script type="application/ld+json">{!! json_encode($person, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => [
      ['name' => 'خانه', 'url' => url('/')],
      ['name' => $member->name, 'url' => $member->profileUrl()],
    ]])
    <section class="hero author-hero">
        <div class="crew-profile-head">
          @if($member->photoUrl())
            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" width="112" height="112" class="crew-profile-photo">
          @endif
          <div>
            <p class="section-hero-kicker">کادر اجرایی</p>
            <h1 style="margin:0 0 .4rem">{{ $member->name }}</h1>
            @if($member->role)
              <p class="crew-card-role">{{ $member->former ? 'سابق — ' : '' }}{{ $member->role }}</p>
            @endif
            @if($member->former && $member->tenure)
              <p class="muted">مدت همکاری در کادر اصلی: {{ $member->tenure }}</p>
            @endif
            @if($member->bio)<p class="muted" style="margin-top:.45rem">{{ $member->bio }}</p>@endif
          </div>
        </div>
    </section>

    <section class="crew-roles-block" aria-labelledby="crew-roles-heading">
        <div class="section-head">
            <h2 class="section-title" id="crew-roles-heading">نقش‌ها در جامعه</h2>
        </div>
        <ul class="crew-roles">
            @if($member->active || $member->role)
              <li class="crew-role-item">
                <span class="crew-role-kind">کادر اصلی</span>
                <strong>{{ $member->former ? 'عضو سابق کادر اجرایی' : 'عضو کادر اجرایی' }}</strong>
                @if($member->role)<p>{{ $member->role }}</p>@endif
              </li>
            @endif
            @forelse($eventRoles as $row)
              <li class="crew-role-item">
                <span class="crew-role-kind">رویداد</span>
                <strong><a href="{{ url('/'.$row->post->slug) }}">{{ $row->post->title }}</a></strong>
                @if($row->role)<p>سمت در این برنامه: {{ $row->role }}</p>@endif
                @if($row->post->published_at)
                  <p class="muted">{{ jdate($row->post->published_at) }}</p>
                @endif
              </li>
            @empty
              @if(!($member->active || $member->role))
                <li class="crew-role-item"><p class="muted">هنوز نقشی ثبت نشده.</p></li>
              @endif
            @endforelse
        </ul>
    </section>

    @if($authored->isNotEmpty())
        <section class="content-section">
            <div class="section-head">
                <h2 class="section-title">نوشته‌ها</h2>
            </div>
            <div class="grid">
                @foreach($authored as $post)
                    @include('site.partials.card', ['post' => $post])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
