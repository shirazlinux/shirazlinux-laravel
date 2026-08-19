@extends('layouts.site')
@section('title','۴۰۴')
@section('robots','noindex,follow')
@section('meta_description','صفحه‌ای که دنبالش بودید پیدا نشد.')
@section('content')
<div class="container">
  <div class="hero hero-404">
    <h1>۴۰۴ — پیدا نشد</h1>
    <p>صفحه‌ای که دنبالش بودید وجود ندارد یا جابه‌جا شده است.</p>
    <div class="intro-actions" style="justify-content:center;margin-top:1rem">
      <a class="btn-brand" href="{{ route('home') }}">بازگشت به خانه</a>
      <a class="btn-outline" href="{{ route('search') }}">جستجو</a>
      <a class="btn-outline" href="{{ route('tags.show','event') }}">نشست‌ها</a>
    </div>
  </div>
</div>
@endsection
