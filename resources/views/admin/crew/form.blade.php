@extends('layouts.admin')
@section('title', $member->exists ? 'ویرایش کادر' : 'عضو کادر جدید')
@section('content')
<h1>{{ $member->exists ? 'ویرایش کادر اجرایی' : 'عضو کادر اجرایی' }}</h1>
<form class="card" method="post" action="{{ $member->exists ? route('admin.crew.update',$member) : route('admin.crew.store') }}" enctype="multipart/form-data">
@csrf
@if($member->exists) @method('PUT') @endif
<div class="row">
  <div>
    <label>نام</label>
    <input name="name" value="{{ old('name',$member->name) }}" required>
  </div>
  <div>
    <label>نقش</label>
    <input name="role" value="{{ old('role',$member->role) }}" placeholder="مثلا: هماهنگی رویداد">
  </div>
</div>
<div class="row">
  <div>
    <label>اسلاگ</label>
    <input name="slug" value="{{ old('slug',$member->slug) }}" dir="ltr" placeholder="auto">
  </div>
  <div>
    <label>ترتیب نمایش</label>
    <input type="number" name="sort" value="{{ old('sort',$member->sort ?? 0) }}" min="0">
  </div>
</div>
<label>مدت همکاری (برای اعضای سابق)</label>
<input name="tenure" value="{{ old('tenure',$member->tenure) }}" placeholder="مثلا: ۱ سال">

<label>بیو کوتاه</label>
<textarea name="bio" style="min-height:100px">{{ old('bio',$member->bio) }}</textarea>

<label>عکس</label>
@if($member->photoUrl())
  <div style="margin:.35rem 0"><img src="{{ $member->photoUrl() }}" alt="" style="width:88px;height:88px;object-fit:cover;border-radius:14px;border:1px solid var(--border)"></div>
@endif
<input type="file" name="photo_file" accept="image/*">
<input name="photo" value="{{ old('photo',$member->photo) }}" dir="ltr" placeholder="یا مسیر/URL">

<div class="row">
  <div><label>وب‌سایت</label><input name="website" value="{{ old('website',$member->website) }}" dir="ltr"></div>
  <div><label>تلگرام</label><input name="telegram" value="{{ old('telegram',$member->telegram) }}" dir="ltr"></div>
</div>
<div class="row">
  <div><label>ماستادون</label><input name="mastodon" value="{{ old('mastodon',$member->mastodon) }}" dir="ltr"></div>
  <div>
    <label>نویسنده متناظر (اختیاری)</label>
    <select name="author_id">
      <option value="">—</option>
      @foreach($authors as $a)
        <option value="{{ $a->id }}" @selected((string)old('author_id',$member->author_id)===(string)$a->id)>{{ $a->name }}</option>
      @endforeach
    </select>
  </div>
</div>
<label style="display:flex;gap:.4rem;align-items:center;margin-top:.8rem">
  <input type="checkbox" name="active" value="1" @checked(old('active', $member->active ?? true))>
  نمایش در صفحه تیم اجرایی
</label>
<label style="display:flex;gap:.4rem;align-items:center;margin-top:.45rem">
  <input type="checkbox" name="former" value="1" @checked(old('former', $member->former ?? false))>
  عضو سابق
</label>

<div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
  <button class="btn" type="submit">ذخیره</button>
  <a class="btn light" href="{{ route('admin.crew.index') }}">بازگشت</a>
</div>
</form>
@if($member->exists)
<form method="post" action="{{ route('admin.crew.destroy',$member) }}" onsubmit="return confirm('حذف شود؟')" style="margin-top:.8rem">
@csrf @method('DELETE')
<button class="btn red" type="submit">حذف</button>
</form>
@endif
@endsection
