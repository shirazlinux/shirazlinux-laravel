@extends('layouts.admin')
@section('title','تگ‌ها')
@section('content')
@php $fa = fn ($n) => \App\Support\JalaliDate::toPersianDigits((string) number_format((int) $n)); @endphp
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
  <h1 style="margin:0">تگ‌ها</h1>
  <a class="btn" href="{{ route('admin.tags.create') }}">+ تگ جدید</a>
</div>

<div class="tag-admin-grid" style="margin-top:1rem">
@forelse($tags as $tag)
  <article class="tag-admin-card">
    <div class="tag-admin-card-top">
      <h2 class="tag-admin-name">{{ $tag->name }}</h2>
      <span class="tag-admin-count">{{ $fa($tag->posts_count) }} مطلب</span>
    </div>
    <code class="tag-admin-slug" dir="ltr">{{ $tag->slug }}</code>
    <div class="tag-admin-actions">
      <a class="btn light" href="{{ route('tags.show', $tag->slug) }}" target="_blank">نمایش</a>
      <a class="btn" href="{{ route('admin.tags.edit', $tag) }}">ویرایش</a>
    </div>
  </article>
@empty
  <div class="card" style="grid-column:1/-1">هنوز تگی ثبت نشده.</div>
@endforelse
</div>
<div style="margin-top:1rem">{!! $tags->links() !!}</div>
@endsection
