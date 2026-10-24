@extends('layouts.admin')
@section('title','نظرات')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
  <h1 style="margin:0">نظرات آزاد</h1>
</div>
<div class="card" style="margin-top:1rem">
  <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1rem">
    @foreach(['all'=>'همه','approved'=>'منتشر','pending'=>'در انتظار','spam'=>'اسپم','trash'=>'زباله'] as $k=>$v)
      <a class="btn {{ $status===$k ? '' : 'light' }}" href="{{ route('admin.comments.index',['status'=>$k]) }}">{{ $v }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
  </div>

  <div class="comment-admin-list">
    @forelse($comments as $c)
      <article class="comment-admin-card {{ $c->isStaff() ? 'is-staff' : '' }}" id="comment-admin-{{ $c->id }}">
        <div class="comment-admin-meta">
          <strong>{{ $c->author_name }}</strong>
          @if($c->isStaff())<span class="comment-staff-pill">پاسخ تیم</span>@endif
          <span>{{ $c->status }}</span>
          <span>{{ jdate($c->created_at, 'Y/m/d H:i') }}</span>
          @if($c->post)
            <a href="{{ url('/'.$c->post->slug) }}#comment-{{ $c->id }}" target="_blank">{{ \Illuminate\Support\Str::limit($c->post->title, 42) }}</a>
          @endif
        </div>
        @if($c->author_email)
          <div dir="ltr" style="font-size:.8rem;color:#78716c;margin-bottom:.35rem">{{ $c->author_email }}</div>
        @endif
        @if($c->parent)
          <p class="jdp-hint" style="margin:.1rem 0 .45rem">پاسخ به {{ $c->parent->author_name }}: {{ \Illuminate\Support\Str::limit($c->parent->body, 90) }}</p>
        @endif
        <div class="comment-admin-body">{{ $c->body }}</div>
        <div class="comment-admin-actions">
          @foreach(['approved'=>'انتشار','pending'=>'انتظار','spam'=>'اسپم','trash'=>'زباله'] as $st=>$lab)
            @if($c->status !== $st)
            <form method="post" action="{{ route('admin.comments.status',$c) }}">@csrf @method('PATCH')
              <input type="hidden" name="status" value="{{ $st }}">
              <button class="btn light" type="submit">{{ $lab }}</button>
            </form>
            @endif
          @endforeach
          <form method="post" action="{{ route('admin.comments.destroy',$c) }}" onsubmit="return confirm('حذف دائمی؟')">@csrf @method('DELETE')
            <button class="btn red" type="submit">حذف</button>
          </form>
        </div>
        @if(! in_array($c->status, ['spam','trash'], true))
          <form class="comment-admin-reply" method="post" action="{{ route('admin.comments.reply',$c) }}">
            @csrf
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="reply-{{ $c->id }}">پاسخ مدیر</label>
            <textarea id="reply-{{ $c->id }}" name="body" required minlength="2" maxlength="2000" rows="3" placeholder="پاسخ را بنویسید. همین حالا منتشر می‌شود."></textarea>
            <button class="btn" type="submit">ارسال پاسخ</button>
          </form>
        @endif
      </article>
    @empty
      <p class="jdp-hint" style="margin:0">نظری در این فهرست نیست.</p>
    @endforelse
  </div>
  <div style="margin-top:1rem">{!! $comments->links() !!}</div>
</div>
@endsection
