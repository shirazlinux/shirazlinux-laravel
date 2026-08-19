@extends('layouts.admin')
@section('title', $author->exists ? 'ویرایش نویسنده' : 'نویسنده جدید')
@section('content')
<h1>{{ $author->exists ? 'ویرایش نویسنده' : 'نویسنده جدید' }}</h1>
<form class="card" method="post" action="{{ $author->exists ? route('admin.authors.update',$author) : route('admin.authors.store') }}" enctype="multipart/form-data">
@csrf
@if($author->exists) @method('PUT') @endif
<div class="row">
  <div>
    <label>نام</label>
    <input name="name" value="{{ old('name',$author->name) }}" required>
  </div>
  <div>
    <label>نام کاربری</label>
    <input name="username" value="{{ old('username',$author->username) }}" dir="ltr">
  </div>
</div>
<label>اسلاگ</label>
<input name="slug" value="{{ old('slug',$author->slug) }}" dir="ltr" placeholder="auto">
<label>بیو</label>
<textarea name="bio" style="min-height:120px">{{ old('bio',$author->bio) }}</textarea>

<label>آواتار</label>
@if($author->avatar)
  @php $av = str_starts_with($author->avatar,'http') ? $author->avatar : asset(ltrim($author->avatar,'/')); @endphp
  <div style="margin:.35rem 0"><img src="{{ $av }}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:999px;border:1px solid var(--border)"></div>
@endif
<input type="file" name="avatar_file" accept="image/*">
<input name="avatar" value="{{ old('avatar',$author->avatar) }}" dir="ltr" placeholder="یا مسیر/URL">

<div class="row">
  <div><label>وب‌سایت</label><input name="website" value="{{ old('website',$author->website) }}" dir="ltr"></div>
  <div><label>تلگرام</label><input name="telegram" value="{{ old('telegram',$author->telegram) }}" dir="ltr"></div>
</div>
<div class="row">
  <div><label>ماس‌تودون</label><input name="mastodon" value="{{ old('mastodon',$author->mastodon) }}" dir="ltr"></div>
  <div><label>GitHub / Codeberg</label><input name="github" value="{{ old('github',$author->github) }}" dir="ltr"></div>
</div>

<div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
  <button class="btn" type="submit">ذخیره</button>
  <a class="btn light" href="{{ route('admin.authors.index') }}">بازگشت</a>
</div>
</form>
@if($author->exists)
<form method="post" action="{{ route('admin.authors.destroy',$author) }}" onsubmit="return confirm('حذف شود؟')" style="margin-top:.8rem">
@csrf @method('DELETE')
<button class="btn red" type="submit">حذف</button>
</form>
@endif
@endsection
