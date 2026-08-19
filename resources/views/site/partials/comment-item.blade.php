<article class="comment-item depth-{{ $depth }}" id="comment-{{ $comment->id }}">
    <header class="comment-meta">
        <strong class="comment-author">
            @if($comment->author_url)
                <a href="{{ $comment->author_url }}" rel="nofollow noopener ugc" target="_blank">{{ $comment->author_name }}</a>
            @else
                {{ $comment->author_name }}
            @endif
        </strong>
        <time datetime="{{ $comment->created_at?->toIso8601String() }}">
            {{ jdate($comment->created_at, 'Y/m/d H:i') }}
        </time>
    </header>
    <div class="comment-body">{{ $comment->body }}</div>
    @if($depth === 0)
        <button type="button" class="linkish comment-reply-btn"
                data-reply-to="{{ $comment->id }}"
                data-reply-name="{{ $comment->author_name }}">پاسخ</button>
    @endif

    @if($comment->relationLoaded('approvedChildren') && $comment->approvedChildren->isNotEmpty())
        <div class="comment-children">
            @foreach($comment->approvedChildren as $child)
                @include('site.partials.comment-item', ['comment' => $child, 'depth' => 1])
            @endforeach
        </div>
    @endif
</article>
