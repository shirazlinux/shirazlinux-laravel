@php
    $comments = $post->relationLoaded('approvedComments')
        ? $post->approvedComments
        : $post->approvedComments()->get();
    $count = $comments->sum(fn ($c) => 1 + $c->approvedChildren->count());
@endphp

<section class="article comments-section" id="comments">
    <div class="comments-head">
        <h2>نظرات آزاد</h2>
        <p class="muted comments-note">
            سیستم نظر <strong>آزاد و شفاف</strong> روی همین سایت است — بدون Disqus و بدون رهگیری شخص ثالث.
            نام کافی است؛ ایمیل اختیاری و منتشر نمی‌شود.
        </p>
        @if($count > 0)
            <p class="comments-count">{{ $count }} نظر</p>
        @endif
    </div>

    @if(session('comment_ok'))
        <div class="ok comment-flash">{{ session('comment_ok') }}</div>
    @endif
    @if($errors->any())
        <div class="notice comment-flash" style="border-color:#fca5a5;background:#fef2f2">
            <ul style="margin:0;padding-inline-start:1.2rem">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form class="comment-form" method="post" action="{{ route('comments.store', $post->slug) }}" id="comment-form">
        @csrf
        <input type="hidden" name="parent_id" id="comment-parent-id" value="">
        {{-- Honeypot --}}
        <div class="hp-field" aria-hidden="true">
            <label>وب‌سایت<input type="text" name="website_hp" value="" tabindex="-1" autocomplete="off"></label>
        </div>

        <p class="comment-reply-hint muted" id="comment-reply-hint" hidden>
            در پاسخ به <strong id="comment-reply-name"></strong>
            <button type="button" class="linkish" id="comment-reply-cancel">لغو</button>
        </p>

        <div class="comment-form-row">
            <div>
                <label for="author_name">نام <span class="req">*</span></label>
                <input id="author_name" name="author_name" value="{{ old('author_name') }}" required maxlength="80" placeholder="نام یا نام مستعار">
            </div>
            <div>
                <label for="author_email">ایمیل <span class="muted">(اختیاری، منتشر نمی‌شود)</span></label>
                <input id="author_email" type="email" name="author_email" value="{{ old('author_email') }}" maxlength="190" placeholder="you@example.com" dir="ltr">
            </div>
        </div>
        <div>
            <label for="author_url">وب‌سایت <span class="muted">(اختیاری)</span></label>
            <input id="author_url" type="url" name="author_url" value="{{ old('author_url') }}" maxlength="255" placeholder="https://" dir="ltr">
        </div>
        <div>
            <label for="comment_body">نظر <span class="req">*</span></label>
            <textarea id="comment_body" name="body" required maxlength="2000" rows="5" placeholder="نظر یا پرسش خود را بنویسید…">{{ old('body') }}</textarea>
            <p class="muted" style="margin:.35rem 0 0;font-size:.82rem">حداکثر ۲۰۰۰ نویسه · لینک زیاد ممکن است برای بررسی نگه داشته شود</p>
        </div>
        <button class="btn-brand" type="submit">ارسال نظر</button>
    </form>

    <div class="comments-list">
        @forelse($comments as $comment)
            @include('site.partials.comment-item', ['comment' => $comment, 'depth' => 0])
        @empty
            <p class="empty-state">هنوز نظری نیست — اولین نفر باشید.</p>
        @endforelse
    </div>
</section>

<script>
(function () {
  var form = document.getElementById('comment-form');
  var parentInput = document.getElementById('comment-parent-id');
  var hint = document.getElementById('comment-reply-hint');
  var nameEl = document.getElementById('comment-reply-name');
  var cancel = document.getElementById('comment-reply-cancel');
  var body = document.getElementById('comment_body');
  if (!form) return;
  document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      parentInput.value = btn.getAttribute('data-reply-to') || '';
      nameEl.textContent = btn.getAttribute('data-reply-name') || '';
      hint.hidden = false;
      body.focus();
      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
  if (cancel) {
    cancel.addEventListener('click', function () {
      parentInput.value = '';
      hint.hidden = true;
    });
  }
})();
</script>
