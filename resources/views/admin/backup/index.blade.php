@extends('layouts.admin')
@section('title','پشتیبان')
@section('content')
<h1>پشتیبان‌گیری</h1>
<div class="card">
  <p>قبل از انتقال به دامنهٔ اصلی، یک خروجی بگیرید و امن نگه دارید.</p>
  <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-top:1rem">
    <a class="btn" href="{{ route('admin.backup.export') }}">دانلود JSON (مطالب + تنظیمات)</a>
    <a class="btn light" href="{{ route('admin.backup.sqlite') }}">دانلود SQLite</a>
  </div>
  <p class="jdp-hint" style="margin-top:1rem">رسانه‌ها (media/) را جداگانه از سرور کپی کنید — در JSON فقط مسیر فایل‌هاست.</p>
</div>
@endsection
