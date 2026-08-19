@extends('layouts.admin')
@section('title','کتابخانه رسانه')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
  <div>
    <h1 style="margin:0">کتابخانه رسانه</h1>
    <p class="jdp-hint" style="margin:.35rem 0 0">تصاویر آپلودشده — قابل استفاده در مطالب و اسلایدر</p>
  </div>
  <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" style="display:flex;gap:.5rem;align-items:center">
    @csrf
    <label class="btn light" style="cursor:pointer;margin:0">
      آپلود تصویر
      <input type="file" name="file" accept="image/*" required hidden onchange="this.form.submit()">
    </label>
  </form>
</div>

<div class="media-admin-grid">
@forelse($items as $item)
  <article class="media-admin-card">
    <a href="{{ $item['url'] }}" target="_blank" class="media-admin-thumb">
      <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" loading="lazy">
    </a>
    <code class="media-admin-path" dir="ltr" title="{{ $item['path'] }}">{{ $item['path'] }}</code>
    <div class="media-admin-actions">
      <button type="button" class="btn light" style="padding:.3rem .55rem;font-size:.8rem" onclick="navigator.clipboard.writeText(@js($item['path']))">کپی مسیر</button>
      <form method="post" action="{{ route('admin.media.destroy') }}" onsubmit="return confirm('حذف شود؟')">
        @csrf @method('DELETE')
        <input type="hidden" name="path" value="{{ $item['path'] }}">
        <button class="btn red" type="submit" style="padding:.3rem .55rem;font-size:.8rem">حذف</button>
      </form>
    </div>
  </article>
@empty
  <div class="card" style="grid-column:1/-1">هنوز تصویری آپلود نشده.</div>
@endforelse
</div>
@endsection
