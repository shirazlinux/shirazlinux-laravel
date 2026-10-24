@if(!empty($gallery) && count($gallery))
<section class="event-gallery-block" aria-labelledby="{{ $galleryHeadingId ?? 'event-gallery-heading' }}">
    <div class="section-head">
        <h2 class="section-title" id="{{ $galleryHeadingId ?? 'event-gallery-heading' }}">{{ $galleryTitle ?? 'عکس نشست‌ها' }}</h2>
        @if(!empty($galleryMoreUrl))
            <a class="section-more" href="{{ $galleryMoreUrl }}">همه</a>
        @endif
    </div>
    @if(!empty($galleryLead))
        <p class="muted section-lead home-desktop-only">{{ $galleryLead }}</p>
    @endif
    <div class="event-gallery" data-lightbox="event">
        @foreach($gallery as $photo)
            <a href="{{ $photo['src'] }}" class="event-gallery-item"
               data-caption="{{ $photo['post_title'] ?? $photo['alt'] }}"
               @if(!empty($photo['post_url'])) data-post="{{ $photo['post_url'] }}" @endif>
                <img src="{{ $photo['thumb'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async" width="320" height="320">
            </a>
        @endforeach
    </div>
</section>
@endif
