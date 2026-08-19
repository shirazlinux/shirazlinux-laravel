@extends('layouts.admin')
@section('title','داشبورد')
@section('content')
@php $fa = fn ($n) => \App\Support\JalaliDate::toPersianDigits((string) number_format((int) $n)); @endphp
<h1>داشبورد</h1>
<div class="stats" style="margin:1rem 0">
  <div class="stat"><b>{{ $posts }}</b>مطلب</div>
  <div class="stat"><b>{{ $pages }}</b>صفحه</div>
  <div class="stat"><b>{{ $published }}</b>منتشرشده</div>
  <div class="stat"><b>{{ $tags }}</b>تگ</div>
  <div class="stat"><b>{{ $authors }}</b>نویسنده</div>
  <div class="stat"><b>{{ $comments }}</b>نظر
    @if($comments_pending)<br><small style="color:#c2410c">{{ $comments_pending }} در انتظار</small>@endif
  </div>
</div>

<div class="card">
  <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem">
    <h2 style="margin:0">بازدید سایت</h2>
    <a href="{{ route('admin.stats') }}" style="font-weight:700">آمار کامل ←</a>
  </div>
  @if(! ($visitSummary['ok'] ?? false))
    <p class="jdp-hint" style="margin:.75rem 0 0">آمار به‌زودی با بازدید صفحات عمومی پر می‌شود.</p>
  @else
    <div class="stats" style="margin:.85rem 0 0">
      <div class="stat"><b>{{ $fa($visitSummary['today']['pageviews'] ?? 0) }}</b>بازدید ۲۴س</div>
      <div class="stat"><b>{{ $fa($visitSummary['today']['visitors'] ?? 0) }}</b>بازدیدکننده ۲۴س</div>
      <div class="stat"><b>{{ $fa($visitSummary['week']['pageviews'] ?? 0) }}</b>بازدید ۷روز</div>
      <div class="stat"><b>{{ $fa($visitSummary['week']['visitors'] ?? 0) }}</b>بازدیدکننده ۷روز</div>
    </div>
  @endif
</div>

@if($latestComments->isNotEmpty())
<div class="card">
<h2>آخرین نظرات <a href="{{ route('admin.comments.index') }}" style="font-size:.9rem;font-weight:600">مدیریت ←</a></h2>
<table>
<tr><th>نویسنده</th><th>نظر</th><th>وضعیت</th><th>مطلب</th></tr>
@foreach($latestComments as $c)
<tr>
<td>{{ $c->author_name }}</td>
<td>{{ \Illuminate\Support\Str::limit($c->body, 80) }}</td>
<td>{{ $c->status }}</td>
<td>@if($c->post)<a href="{{ url('/'.$c->post->slug) }}" target="_blank">{{ \Illuminate\Support\Str::limit($c->post->title, 30) }}</a>@endif</td>
</tr>
@endforeach
</table>
</div>
@endif

<div class="card">
<h2>آخرین ویرایش‌ها</h2>
<table>
<tr><th>عنوان</th><th>نوع</th><th>وضعیت</th><th>به‌روز</th><th></th></tr>
@foreach($latest as $p)
<tr>
<td>{{ $p->title }}</td>
<td>{{ $p->type }}</td>
<td>{{ $p->status }}</td>
<td>{{ jdate($p->updated_at, 'Y/m/d H:i') }}</td>
<td><a href="{{ route('admin.posts.edit',$p) }}">ویرایش</a></td>
</tr>
@endforeach
</table>
</div>
@endsection
