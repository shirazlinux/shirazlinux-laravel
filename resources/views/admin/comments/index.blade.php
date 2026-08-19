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
  <table>
    <tr><th>نویسنده</th><th>نظر</th><th>مطلب</th><th>وضعیت</th><th>تاریخ</th><th></th></tr>
    @foreach($comments as $c)
    <tr>
      <td>
        <strong>{{ $c->author_name }}</strong>
        @if($c->author_email)<br><span dir="ltr" style="font-size:.8rem;color:#666">{{ $c->author_email }}</span>@endif
        @if($c->ip)<br><span dir="ltr" style="font-size:.75rem;color:#999">{{ $c->ip }}</span>@endif
      </td>
      <td style="max-width:280px">{{ \Illuminate\Support\Str::limit($c->body, 140) }}</td>
      <td>
        @if($c->post)
          <a href="{{ url('/'.$c->post->slug) }}#comment-{{ $c->id }}" target="_blank">{{ \Illuminate\Support\Str::limit($c->post->title, 40) }}</a>
        @endif
      </td>
      <td>{{ $c->status }}</td>
      <td style="white-space:nowrap">{{ jdate($c->created_at, 'Y/m/d H:i') }}</td>
      <td style="white-space:nowrap">
        @foreach(['approved'=>'✓','pending'=>'⏳','spam'=>'⛔','trash'=>'🗑'] as $st=>$lab)
          @if($c->status !== $st)
          <form method="post" action="{{ route('admin.comments.status',$c) }}" style="display:inline">@csrf @method('PATCH')
            <input type="hidden" name="status" value="{{ $st }}">
            <button class="btn light" type="submit" title="{{ $st }}">{{ $lab }}</button>
          </form>
          @endif
        @endforeach
        <form method="post" action="{{ route('admin.comments.destroy',$c) }}" style="display:inline" onsubmit="return confirm('حذف دائمی؟')">@csrf @method('DELETE')
          <button class="btn red" type="submit">حذف</button>
        </form>
      </td>
    </tr>
    @endforeach
  </table>
  <div style="margin-top:1rem">{!! $comments->links() !!}</div>
</div>
@endsection
