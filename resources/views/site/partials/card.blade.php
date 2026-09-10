@php
    $url = url('/'.$post->slug);
    $eventSlugs = ['event', 'dorehami', 'workshop', 'conference', 'freesoftwaretalks'];
    $aspect = $imageAspect ?? null;
    $compact = ! empty($compact);

    $isSquare = false;
    $isWide = false;
    if ($aspect === '1/1' || $aspect === 'square' || $aspect === true) {
        $isSquare = true;
    } elseif ($aspect === '16/9' || $aspect === 'wide') {
        $isWide = true;
    } elseif ($aspect === false || $aspect === '16/10' || $aspect === 'landscape') {
        $isWide = true; // landscape covers
    } else {
        // auto: event-tagged posts use square posters (Publii source art is often 1:1)
        $isSquare = $post->relationLoaded('tags')
            && $post->tags->contains(fn ($t) => in_array($t->slug, $eventSlugs, true));
    }
    $cardClass = trim(
        ($isSquare ? 'card--square' : '').' '.
        ($isWide ? 'card--wide' : '').' '.
        ($compact ? 'card--compact' : '')
    );
@endphp
<article class="card c-card {{ $cardClass }}">
    <a class="card-media c-card__image" href="{{ $url }}">
        @if($post->featured_image)
            <img src="{{ asset(ltrim($post->featured_image, '/')) }}" alt="{{ $post->title }}" loading="lazy"
                 onerror="this.src='{{ asset('media/website/logo.png') }}';this.style.objectFit='contain';this.style.background='#1c1917';this.style.padding='1rem'">
        @else
            <img src="{{ asset('media/website/logo.png') }}" alt="{{ $post->title }}" loading="lazy" style="object-fit:contain;background:#1c1917;padding:1.25rem">
        @endif
    </a>
    <div class="card-body c-card__wrapper">
        <div class="card-meta">
            @if($post->type === 'page')
                <span class="badge">صفحه</span>
            @endif
            @foreach($post->tags->take(1) as $tag)
                <a class="badge" href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>
            @endforeach
            @if($post->published_at)
                <span class="card-date">{{ jdate($post->published_at) }}</span>
            @endif
        </div>
        <h2 class="c-card__title"><a href="{{ $url }}">{{ $post->title }}</a></h2>
        @if($post->excerpt && ! $compact)
            <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 120) }}</p>
        @elseif($post->excerpt)
            <p class="card-excerpt card-excerpt--short">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 72) }}</p>
        @endif
        @unless($compact)
            <div class="card-footer-meta">
                @if($post->author)
                    <a href="{{ route('authors.show', $post->author->slug) }}">{{ $post->author->name }}</a>
                @endif
                @foreach($post->tags->skip(1)->take(2) as $tag)
                    <a class="badge" href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a>
                @endforeach
            </div>
        @endunless
    </div>
</article>
