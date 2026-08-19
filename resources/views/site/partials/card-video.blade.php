@php
    $url = url('/'.$post->slug);
@endphp
<article class="card c-card card-video">
    <a class="card-media c-card__image video-thumb" href="{{ $url }}">
        @if($post->featured_image)
            <img src="{{ asset(ltrim($post->featured_image, '/')) }}" alt="{{ $post->title }}" loading="lazy"
                 onerror="this.src='{{ asset('media/website/logo.png') }}';this.style.objectFit='contain';this.style.background='#1c1917';this.style.padding='1rem'">
        @else
            <img src="{{ asset('media/website/logo.png') }}" alt="" loading="lazy" style="object-fit:contain;background:#1c1917;padding:1.25rem">
        @endif
        <span class="video-play" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="44" height="44">
                <circle cx="24" cy="24" r="22" fill="rgba(28,25,23,.55)"/>
                <path d="M20 15.5v17l14-8.5-14-8.5z" fill="#fff"/>
            </svg>
        </span>
        <span class="video-chip">ویدیو</span>
    </a>
    <div class="card-body c-card__wrapper">
        <div class="card-meta">
            <a class="badge" href="{{ route('tags.show', 'videos') }}">ویدیوها</a>
            @if($post->published_at)
                <span class="card-date">{{ jdate($post->published_at) }}</span>
            @endif
        </div>
        <h2 class="c-card__title"><a href="{{ $url }}">{{ $post->title }}</a></h2>
        @if($post->excerpt)
            <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 110) }}</p>
        @endif
        <a class="video-cta" href="{{ $url }}">تماشا ←</a>
    </div>
</article>
