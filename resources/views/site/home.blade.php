@extends('layouts.site')
@php
  $homeTitle = setting('site_name', 'شیرازلینوکس');
  $homeDesc = setting('seo_default_description', setting('site_description', 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز — نشست، آموزش و ترویج آزادی کاربران.'));
  $homeOg = setting('seo_og_image') ?: 'media/slider/slide1.jpg';
@endphp
@section('title', $homeTitle)
@section('meta_description', $homeDesc)
@section('og_type', 'website')
@section('og_image', $homeOg)
@section('canonical', url('/'))

@section('content')
@if(setting('home_show_slider', true) && !empty($slides))
<section class="home-slider" aria-label="اسلایدر">
    <div class="slider" id="home-slider" data-interval="4000">
        @foreach($slides as $i => $slide)
            <div class="slide{{ $i === 0 ? ' is-active' : '' }}" data-index="{{ $i }}">
                <img src="{{ $slide['image'] }}" alt="{{ $slide['alt'] }}" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                <div class="slide-overlay">
                    <div class="slide-caption">
                        <span class="slide-kicker">{{ setting('site_name', 'شیرازلینوکس') }}</span>
                        <h2>{{ $slide['title'] }}</h2>
                        @if(!empty($slide['url']))
                            <a class="slide-btn" href="{{ $slide['url'] }}">بیشتر</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <div class="slider-dots" role="tablist" aria-label="اسلایدها">
            @foreach($slides as $i => $slide)
                <button type="button" class="dot{{ $i === 0 ? ' is-active' : '' }}" data-go="{{ $i }}" aria-label="اسلاید {{ $i + 1 }}"></button>
            @endforeach
        </div>
        <button type="button" class="slider-nav prev" data-dir="-1" aria-label="قبلی">›</button>
        <button type="button" class="slider-nav next" data-dir="1" aria-label="بعدی">‹</button>
    </div>
</section>
@endif

<div class="container home-wrap home-page">
    <section class="intro-band">
        <h1 class="h1main">{{ setting('home_intro_title', 'شیرازلینوکس؛ جامعه نرم‌افزار آزاد شیراز') }}</h1>
        <p class="intro-text">{!! nl2br(e(setting('home_intro_text', "ما یک جامعه هستیم در شیراز؛ دور هم جمع می‌شویم، یاد می‌گیریم و نرم‌افزار آزاد را ترویج می‌کنیم.\nاگر دنبال نشست، آموزش، یا راهی برای شروع با گنو/لینوکس می‌گردی، جای درستی آمده‌ای."))) !!}</p>
        <div class="intro-actions">
            <a class="btn-brand" href="{{ route('tags.show','event') }}">نشست‌ها</a>
            <a class="btn-outline" href="{{ route('tags.show','dorehami') }}">دورهمی‌ها</a>
            <a class="btn-outline" href="{{ url('/what-is-free-software') }}">نرم‌افزار آزاد چیست؟</a>
            <a class="btn-outline" href="{{ route('tags.show','videos') }}">ویدیوها</a>
            <a class="btn-outline home-desktop-only" href="{{ url('/donate') }}">حمایت از جامعه</a>
        </div>
    </section>

    <nav class="home-sitelinks" aria-label="پیوندهای مهم">
        <div class="home-sitelinks-head">
            <h2 class="home-sitelinks-title">بخش‌های سایت</h2>
        </div>
        <ul class="home-sitelinks-grid">
            @foreach(\App\Support\NavMenu::sitelinks() as $link)
                <li>
                    <a class="home-sitelink" href="{{ $link['url'] }}">
                        <strong>{{ $link['name'] }}</strong>
                        @if(!empty($link['description']))
                            <span>{{ $link['description'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @if(setting('home_show_edu', true))
        @include('site.partials.home-edu')
    @endif

    @if(setting('home_show_history', true) && $historyPosts->isNotEmpty())
        <section class="content-section section-history" aria-labelledby="history-heading">
            <div class="section-head">
                <h2 class="section-title" id="history-heading">تاریخچه نرم‌افزار آزاد</h2>
                <a class="section-more" href="{{ url('/free-software-history/') }}">همه</a>
            </div>
            <p class="muted section-lead home-desktop-only">
                از شروع پروژه گنو تا مجوزها و بنیاد نرم‌افزار آزاد — چند مطلب کوتاه برای شناخت مسیر این جنبش.
            </p>
            <div class="grid cards-history home-scroll-row">
                @foreach($historyPosts as $i => $post)
                    <div class="home-scroll-item{{ $i >= 4 ? ' home-desktop-only' : '' }}">
                        @include('site.partials.card', ['post' => $post, 'compact' => true])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(setting('home_show_projects', true))
    <section class="content-section section-projects">
        <div class="section-head">
            <h2 class="section-title">پروژه‌های جامعه</h2>
        </div>
        <p class="muted section-lead home-desktop-only">
            سرویس‌ها و پروژه‌هایی که جامعه شیرازلینوکس ساخته یا میزبانی می‌کند.
        </p>
        <div class="projects-grid home-scroll-row">
            @foreach($projects as $project)
                @php
                    $isExternal = str_starts_with($project['url'], 'http')
                        && ! str_starts_with($project['url'], rtrim(url('/'), '/'));
                @endphp
                <a class="project-card home-scroll-item" href="{{ $project['url'] }}"
                   @if($isExternal) target="_blank" rel="noopener" @endif>
                    <span class="project-media" aria-hidden="true">
                        @if(!empty($project['image']))
                            <img src="{{ $project['image'] }}" alt="" loading="lazy" decoding="async"
                                 width="400" height="250">
                        @else
                            <span class="project-emoji project-emoji--fallback">{{ $project['emoji'] ?? '🚀' }}</span>
                        @endif
                        <span class="project-tag">{{ $project['tag'] }}</span>
                    </span>
                    <span class="project-body">
                        <strong class="project-name">{{ $project['name'] }}</strong>
                        <span class="project-desc">{{ $project['desc'] }}</span>
                        <span class="project-go">مشاهده</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if(setting('home_show_videos', true) && $videoPosts->isNotEmpty())
        <section class="content-section section-videos">
            <div class="section-head">
                <h2 class="section-title">ویدیوها</h2>
                <a class="section-more" href="{{ route('tags.show','videos') }}">همه</a>
            </div>
            <p class="muted section-lead home-desktop-only">
                چند ویدیو و انیمیشن درباره آزادی در نرم‌افزار — برای شروع گفتگو و یادگیری.
            </p>
            <div class="grid cards-video home-scroll-row">
                @foreach($videoPosts as $i => $post)
                    <div class="home-scroll-item{{ $i >= 3 ? ' home-desktop-only' : '' }}">
                        @include('site.partials.card-video', ['post' => $post])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="home-explore" aria-label="برچسب‌ها">
        <div class="home-explore-head">
            <h2 class="home-explore-title">برچسب‌ها</h2>
            <a class="section-more" href="{{ route('tags.index') }}">همه برچسب‌ها</a>
        </div>

        <div class="home-explore-tags">
            @foreach($tags->take(10) as $tag)
                @if($tag->posts_count > 0)
                    <a class="home-explore-chip" href="{{ route('tags.show', $tag->slug) }}">
                        {{ $tag->name }}
                        <span>{{ $tag->posts_count }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        <nav class="home-explore-links" aria-label="پیوندهای اصلی">
            <a href="{{ route('tags.show','event') }}">نشست‌ها</a>
            <a href="{{ route('tags.show','dorehami') }}">دورهمی‌ها</a>
            <a href="{{ route('tags.show','workshop') }}">کارگاه‌ها</a>
            <a href="{{ route('tags.show','videos') }}">ویدیوها</a>
            <a href="{{ url('/about') }}">درباره</a>
            <a href="{{ url('/donate') }}">حمایت</a>
        </nav>
    </section>
</div>
@endsection
