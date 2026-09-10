@extends('layouts.site')
@section('title', $q !== '' ? 'جستجو: '.$q : 'جستجو')
@section('meta_description', 'جستجو در مطالب، صفحات، تگ‌ها و نویسندگان شیرازلینوکس')
@section('robots', 'noindex,follow')

@section('content')
<div class="container search-page">
    <section class="hero">
        <h1>جستجو</h1>
        <p class="muted">عنوان، خلاصه، متن، تگ و نویسنده — با رتبه‌بندی مرتبط‌ترین‌ها.</p>
        <form class="search-form search-form-lg" action="{{ route('search') }}" method="get" role="search">
            <input type="search" name="q" value="{{ $q }}" placeholder="مثلاً: نرم‌افزار آزاد، نشست، ویدیو…"
                   autofocus aria-label="عبارت جستجو" minlength="2" autocomplete="off">
            <button type="submit">بگرد</button>
        </form>
        <div class="search-filters" role="group" aria-label="نوع نتیجه">
            @foreach(['all' => 'همه', 'post' => 'مطالب', 'page' => 'صفحات'] as $key => $label)
                <a class="filter-chip {{ $type === $key ? 'is-active' : '' }}"
                   href="{{ route('search', array_filter(['q' => $q ?: null, 'type' => $key === 'all' ? null : $key])) }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    <div class="layout search-layout">
        <div>
            @if($q === '')
                <div class="content-section">
                    <h2 class="section-title">پیشنهادها</h2>
                    <p class="muted">حداقل ۲ حرف بنویسید. چند کلمه با فاصله = همه باید پیدا شوند.</p>
                    <div class="tag-cloud" style="margin-top:.8rem;gap:.5rem">
                        @foreach($popularQueries as $pq)
                            <a href="{{ route('search', ['q' => $pq]) }}">{{ $pq }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="search-meta muted">
                    نتایج برای «<strong>{{ $q }}</strong>»:
                    <strong>{{ $total }}</strong> مورد
                    @if($type !== 'all')
                        · فیلتر: {{ $type === 'post' ? 'مطالب' : 'صفحات' }}
                    @endif
                </p>

                @if($posts->isEmpty())
                    <div class="notice">
                        چیزی پیدا نشد.
                        @if(count($tokens) > 1)
                            کلمات کمتری امتحان کنید یا املا را ساده‌تر کنید.
                        @else
                            عبارت دیگری بنویسید یا از برچسب‌ها شروع کنید.
                        @endif
                        <div class="tag-cloud" style="margin-top:.75rem">
                            @foreach($popularQueries as $pq)
                                <a href="{{ route('search', ['q' => $pq]) }}">{{ $pq }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="search-results">
                        @foreach($posts as $post)
                            @php $url = url('/'.$post->slug); @endphp
                            <article class="search-hit">
                                <div class="search-hit-media">
                                    <a href="{{ $url }}">
                                        @if($post->featured_image)
                                            <img src="{{ asset(ltrim($post->featured_image, '/')) }}" alt="{{ $post->title }}" loading="lazy"
                                                 onerror="this.style.display='none'">
                                        @else
                                            <span class="search-hit-placeholder">{{ $post->type === 'page' ? 'صفحه' : 'مطلب' }}</span>
                                        @endif
                                    </a>
                                </div>
                                <div class="search-hit-body">
                                    <div class="card-meta">
                                        @if($post->type === 'page')
                                            <span class="badge">صفحه</span>
                                        @endif
                                        @foreach($post->tags->take(2) as $tag)
                                            <a class="badge" href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>
                                        @endforeach
                                        @if($post->published_at)
                                            <span class="card-date">{{ jdate($post->published_at) }}</span>
                                        @endif
                                    </div>
                                    <h2 class="search-hit-title">
                                        <a href="{{ $url }}">{{ $post->title }}</a>
                                    </h2>
                                    @if(!empty($post->search_snippet))
                                        <p class="search-snippet">{!! $post->search_snippet !!}</p>
                                    @elseif($post->excerpt)
                                        <p class="search-snippet">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 160) }}</p>
                                    @endif
                                    <a class="video-cta" href="{{ $url }}">مشاهده ←</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="pagination">{!! $posts->withQueryString()->links() !!}</div>
                @endif
            @endif
        </div>

        <aside class="sidebar">
            @if(isset($matchedTags) && $matchedTags->isNotEmpty())
                <div class="panel panel-brand">
                    <h3>تگ‌های مرتبط</h3>
                    <div class="page-list">
                        @foreach($matchedTags as $tag)
                            <a href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }} <span class="muted">({{ $tag->posts_count }})</span></a>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="panel">
                <h3>برچسب‌های پرتکرار</h3>
                <div class="tag-cloud">
                    @foreach($suggestedTags as $tag)
                        <a href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>
            <div class="panel">
                <h3>میان‌بر</h3>
                <div class="page-list">
                    <a href="{{ route('tags.show','videos') }}">ویدیوها</a>
                    <a href="{{ route('tags.show','event') }}">نشست‌ها</a>
                    <a href="{{ url('/what-is-free-software') }}">نرم‌افزار آزاد چیه؟</a>
                    <a href="{{ route('authors.index') }}">نویسندگان</a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
